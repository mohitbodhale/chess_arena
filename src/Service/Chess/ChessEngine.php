<?php
declare(strict_types=1);

namespace App\Service\Chess;

use InvalidArgumentException;

/**
 * Authoritative chess rules engine.
 *
 * The engine owns the canonical game state (FEN), generates legal moves,
 * validates them, tracks the move history and renders PGN. The browser side
 * uses chess.js for instant feedback, but every move that reaches the
 * database is re-validated here so a tampered client cannot forge a result.
 *
 * Board squares are addressed algebraically (`a1` .. `h8`). The internal board
 * is an associative array keyed by square:
 *
 * ```
 * $board['e4'] = ['type' => 'p', 'color' => 'w'];
 * ```
 */
class ChessEngine
{
    public const WHITE = 'w';
    public const BLACK = 'b';

    /**
     * FEN of the standard starting position.
     */
    public const START_FEN = 'rnbqkbnr/pppppppp/8/8/8/8/PPPPPPPP/RNBQKBNR w KQkq - 0 1';

    /**
     * Material values used by the evaluation bar.
     *
     * @var array<string, int>
     */
    public const PIECE_VALUES = [
        'p' => 1,
        'n' => 3,
        'b' => 3,
        'r' => 5,
        'q' => 9,
        'k' => 0,
    ];

    /**
     * @var array<string, array{type: string, color: string}>
     */
    protected array $board = [];

    protected string $turn = self::WHITE;

    /**
     * Castling availability, e.g. `KQkq`.
     */
    protected string $castling = 'KQkq';

    /**
     * En passant target square, e.g. `e3`.
     */
    protected ?string $enPassant = null;

    protected int $halfmoveClock = 0;

    protected int $fullmoveNumber = 1;

    /**
     * Moves applied since the position was loaded.
     *
     * @var array<int, array<string, mixed>>
     */
    protected array $history = [];

    /**
     * @var array<int, array<string, mixed>>
     */
    protected array $positionKeys = [];

    /**
     * Constructor.
     *
     * @param string|null $fen Starting position, defaults to the standard start.
     */
    public function __construct(?string $fen = null)
    {
        $this->loadFen($fen ?? self::START_FEN);
    }

    /**
     * Load a position from a FEN string.
     *
     * @param string $fen FEN string.
     * @return $this
     */
    public function loadFen(string $fen): static
    {
        $parts = explode(' ', trim($fen));
        if (count($parts) < 4) {
            throw new InvalidArgumentException('Malformed FEN: expected six fields.');
        }

        [$placement, $turn, $castling, $enPassant] = $parts;
        $halfmove = isset($parts[4]) && is_numeric($parts[4]) ? (int)$parts[4] : 0;
        $fullmove = isset($parts[5]) && is_numeric($parts[5]) ? (int)$parts[5] : 1;

        $board = [];
        $rows = explode('/', $placement);
        if (count($rows) !== 8) {
            throw new InvalidArgumentException('Malformed FEN: expected eight ranks.');
        }

        foreach ($rows as $index => $row) {
            $rank = 8 - $index;
            $file = 0;
            $length = strlen($row);
            for ($i = 0; $i < $length; $i++) {
                $char = $row[$i];
                if (ctype_digit($char)) {
                    $file += (int)$char;

                    continue;
                }
                if (!in_array($char, ['p', 'n', 'b', 'r', 'q', 'k', 'P', 'N', 'B', 'R', 'Q', 'K'], true)) {
                    throw new InvalidArgumentException(sprintf('Malformed FEN: unknown piece `%s`.', $char));
                }
                $color = ctype_upper($char) ? self::WHITE : self::BLACK;
                $board[$this->square($file, $rank)] = [
                    'type' => strtolower($char),
                    'color' => $color,
                ];
                $file++;
            }
        }

        $this->board = $board;
        $this->turn = $turn === self::BLACK ? self::BLACK : self::WHITE;
        $this->castling = $castling === '-' ? '' : $castling;
        $this->enPassant = $enPassant === '-' ? null : $enPassant;
        $this->halfmoveClock = $halfmove;
        $this->fullmoveNumber = max(1, $fullmove);
        $this->history = [];
        $this->positionKeys = [$this->positionKey()];

        return $this;
    }

    /**
     * Render the current position as a FEN string.
     *
     * @return string FEN string.
     */
    public function toFen(): string
    {
        $rows = [];
        for ($rank = 8; $rank >= 1; $rank--) {
            $row = '';
            $empty = 0;
            for ($file = 0; $file < 8; $file++) {
                $piece = $this->board[$this->square($file, $rank)] ?? null;
                if ($piece === null) {
                    $empty++;

                    continue;
                }
                if ($empty > 0) {
                    $row .= (string)$empty;
                    $empty = 0;
                }
                $row .= $piece['color'] === self::WHITE
                    ? strtoupper($piece['type'])
                    : $piece['type'];
            }
            if ($empty > 0) {
                $row .= (string)$empty;
            }
            $rows[] = $row;
        }

        return sprintf(
            '%s %s %s %s %d %d',
            implode('/', $rows),
            $this->turn,
            $this->castling === '' ? '-' : $this->castling,
            $this->enPassant ?? '-',
            $this->halfmoveClock,
            $this->fullmoveNumber,
        );
    }

    /**
     * The board as a plain array of squares.
     *
     * @return array<string, array{type: string, color: string}>
     */
    public function board(): array
    {
        return $this->board;
    }

    /**
     * Colour of the side to move.
     *
     * @return string `w` or `b`.
     */
    public function turn(): string
    {
        return $this->turn;
    }

    /**
     * The en passant target square.
     */
    public function enPassant(): ?string
    {
        return $this->enPassant;
    }

    /**
     * Halfmove clock (plies since last capture or pawn move).
     */
    public function halfmoveClock(): int
    {
        return $this->halfmoveClock;
    }

    /**
     * Fullmove number.
     */
    public function fullmoveNumber(): int
    {
        return $this->fullmoveNumber;
    }

    /**
     * Moves played since the position was loaded.
     *
     * @return array<int, array<string, mixed>>
     */
    public function history(): array
    {
        return $this->history;
    }

    /**
     * Replay a list of moves (UCI notation) on a fresh engine.
     *
     * @param array<int, string> $moves UCI moves, e.g. `['e2e4', 'e7e5']`.
     * @return $this
     * @throws InvalidArgumentException When a move is illegal.
     */
    public function replay(array $moves): static
    {
        $this->loadFen(self::START_FEN);
        foreach ($moves as $move) {
            if ($this->move((string)$move) === null) {
                throw new InvalidArgumentException(sprintf('Illegal move in replay: `%s`.', $move));
            }
        }

        return $this;
    }

    /**
     * Generate every legal move for the side to move.
     *
     * @param string|null $from Restrict generation to a single origin square.
     * @return array<int, array<string, mixed>>
     */
    public function legalMoves(?string $from = null): array
    {
        $moves = [];
        foreach ($this->board as $square => $piece) {
            if ($from !== null && $square !== $from) {
                continue;
            }
            if ($piece['color'] !== $this->turn) {
                continue;
            }
            foreach ($this->generateMoves($square, $piece) as $move) {
                if ($this->isLegal($move)) {
                    $moves[] = $move;
                }
            }
        }

        return $moves;
    }

    /**
     * Legal destination squares for a given origin square.
     *
     * @param string $from Origin square.
     * @return array<int, string>
     */
    public function legalTargets(string $from): array
    {
        $targets = [];
        foreach ($this->legalMoves($from) as $move) {
            $targets[] = $move['to'];
        }

        return array_values(array_unique($targets));
    }

    /**
     * Apply a move given in UCI notation (e.g. `e2e4`, `e7e8q`).
     *
     * @param string $uci Move in UCI notation.
     * @return array<string, mixed>|null The applied move, or null when illegal.
     */
    public function move(string $uci): ?array
    {
        $uci = strtolower(trim($uci));
        $from = substr($uci, 0, 2);
        $to = substr($uci, 2, 2);
        $promotion = strlen($uci) >= 5 ? $uci[4] : null;

        // When a promotion piece is omitted a pawn reaching the last rank
        // promotes to a queen, which is the common default.
        $promotionFallback = null;

        foreach ($this->legalMoves($from) as $move) {
            if ($move['to'] !== $to) {
                continue;
            }
            if ($promotion !== null && ($move['promotion'] ?? null) !== $promotion) {
                continue;
            }
            if ($promotion === null && ($move['promotion'] ?? null) !== null) {
                if (($move['promotion'] ?? null) === 'q') {
                    $promotionFallback = $move;
                }

                continue;
            }

            return $this->makeMove($move);
        }

        if ($promotionFallback !== null) {
            return $this->makeMove($promotionFallback);
        }

        return null;
    }

    /**
     * Apply a move given in SAN notation (e.g. `Nf3`, `exd5`, `O-O`).
     *
     * @param string $san Move in SAN notation.
     * @return array<string, mixed>|null The applied move, or null when illegal.
     */
    public function moveSan(string $san): ?array
    {
        $move = $this->parseSan($san);
        if ($move === null) {
            return null;
        }

        return $this->makeMove($move);
    }

    /**
     * Resolve a SAN token against the legal moves of the current position.
     *
     * @param string $san Move in SAN notation.
     * @return array<string, mixed>|null
     */
    public function parseSan(string $san): ?array
    {
        $san = trim(str_replace(['+', '#', '!', '?'], '', $san));
        if ($san === '') {
            return null;
        }

        $castling = [
            'O-O' => ['K', 'K'],
            'O-O-O' => ['Q', 'Q'],
            '0-0' => ['K', 'K'],
            '0-0-0' => ['Q', 'Q'],
        ];
        if (isset($castling[$san])) {
            [, $right] = $castling[$san];
            foreach ($this->legalMoves() as $move) {
                if (($move['castle'] ?? null) === $right) {
                    return $move;
                }
            }

            return null;
        }

        $match = [];
        if (!preg_match('/^(?<piece>[NBRQK])?(?<fromFile>[a-h])?(?<fromRank>[1-8])?(?<capture>x)?(?<to>[a-h][1-8])(?:=(?<promo>[QRBN]))?$/i', $san, $match)) {
            return null;
        }

        $pieceType = strtolower($match['piece'] ?? '');
        $promotion = isset($match['promo']) && $match['promo'] !== '' ? strtolower($match['promo']) : null;
        $to = strtolower($match['to']);
        $fromFile = $match['fromFile'] ?? '';
        $fromRank = $match['fromRank'] ?? '';

        $candidates = [];
        foreach ($this->legalMoves() as $move) {
            if ($move['to'] !== $to) {
                continue;
            }
            if ($pieceType !== '' && $move['type'] !== $pieceType) {
                continue;
            }
            if ($pieceType === '' && $move['type'] !== 'p') {
                continue;
            }
            if ($promotion !== null && ($move['promotion'] ?? null) !== $promotion) {
                continue;
            }
            if ($promotion === null && ($move['promotion'] ?? null) !== null) {
                continue;
            }
            if ($fromFile !== '' && $move['from'][0] !== $fromFile) {
                continue;
            }
            if ($fromRank !== '' && $move['from'][1] !== $fromRank) {
                continue;
            }
            $candidates[] = $move;
        }

        if (count($candidates) === 1) {
            return $candidates[0];
        }

        return null;
    }

    /**
     * Render a move in SAN notation.
     *
     * @param array<string, mixed> $move A move as returned by `legalMoves()`.
     * @return string
     */
    public function san(array $move): string
    {
        // Disambiguation depends on the position *before* the move is applied.
        $disambiguation = $this->disambiguation($move);

        $snapshot = $this->applyState($move);
        $san = $this->buildSan($move, $disambiguation);
        $this->restoreState($snapshot);

        return $san;
    }

    /**
     * Whether the side to move is in check.
     */
    public function isCheck(): bool
    {
        $king = $this->findKing($this->turn);

        return $king !== null && $this->isAttacked($king, $this->opponent($this->turn));
    }

    /**
     * Whether the side to move is checkmated.
     */
    public function isCheckmate(): bool
    {
        return $this->isCheck() && $this->legalMoves() === [];
    }

    /**
     * Whether the side to move is stalemated.
     */
    public function isStalemate(): bool
    {
        return !$this->isCheck() && $this->legalMoves() === [];
    }

    /**
     * Whether neither side has enough material to mate.
     */
    public function isInsufficientMaterial(): bool
    {
        $pieces = [];
        foreach ($this->board as $piece) {
            if ($piece['type'] === 'k') {
                continue;
            }
            $pieces[] = $piece;
        }

        if ($pieces === []) {
            return true;
        }
        if (count($pieces) === 1) {
            return true;
        }
        if (count($pieces) === 2) {
            $types = array_column($pieces, 'type');
            sort($types);

            return $types === ['b', 'b'] || $types === ['b', 'n'];
        }

        return false;
    }

    /**
     * Whether the game is finished.
     */
    public function isGameOver(): bool
    {
        return $this->isCheckmate()
            || $this->isStalemate()
            || $this->isInsufficientMaterial()
            || $this->halfmoveClock >= 100;
    }

    /**
     * Reason the game ended, or null while it is still running.
     */
    public function terminationReason(): ?string
    {
        if ($this->isCheckmate()) {
            return 'checkmate';
        }
        if ($this->isStalemate()) {
            return 'stalemate';
        }
        if ($this->isInsufficientMaterial()) {
            return 'insufficient_material';
        }
        if ($this->halfmoveClock >= 100) {
            return 'fifty_move_rule';
        }

        return null;
    }

    /**
     * Static evaluation of the position from white's point of view.
     *
     * The score is material only, which is enough to drive the evaluation bar.
     * Positive values favour white.
     */
    public function evaluate(): float
    {
        $score = 0.0;
        foreach ($this->board as $piece) {
            $value = self::PIECE_VALUES[$piece['type']];
            $score += $piece['color'] === self::WHITE ? $value : -$value;
        }

        return $score;
    }

    /**
     * Export the game as PGN.
     *
     * @param array<string, string> $headers PGN headers.
     * @return string
     */
    public function pgn(array $headers = []): string
    {
        $replay = clone $this;
        $replay->loadFen(self::START_FEN);

        $moves = [];
        foreach ($this->history as $move) {
            $moves[] = $replay->san($move);
            $replay->makeMove($move);
        }

        $headers = array_merge([
            'Event' => 'Chess Arena',
            'Site' => 'Chess Arena',
            'Date' => date('Y.m.d'),
            'Round' => '-',
            'White' => '?',
            'Black' => '?',
            'Result' => '*',
        ], $headers);

        $lines = [];
        foreach ($headers as $name => $value) {
            $lines[] = sprintf('[%s "%s"]', $name, $value);
        }

        $body = '';
        foreach ($moves as $index => $san) {
            if ($index % 2 === 0) {
                $body .= sprintf('%d. ', intdiv($index, 2) + 1);
            }
            $body .= $san . ' ';
        }
        $body = trim($body);

        return implode("\n", $lines) . "\n\n" . $body . "\n";
    }

    /**
     * Apply a legal move to the current position.
     *
     * @param array<string, mixed> $move A move as returned by `legalMoves()`.
     * @return array<string, mixed> The applied move with SAN attached.
     */
    protected function makeMove(array $move): array
    {
        $san = $this->san($move);

        $this->applyState($move);

        $move['san'] = $san;
        $move['check'] = $this->isCheck();
        $this->history[] = $move;
        $this->positionKeys[] = $this->positionKey();

        return $move;
    }

    /**
     * Mutate the position with a move, returning the previous state.
     *
     * @param array<string, mixed> $move The move to apply.
     * @return array<string, mixed> Snapshot used to restore the state.
     */
    protected function applyState(array $move): array
    {
        $snapshot = [
            'board' => $this->board,
            'turn' => $this->turn,
            'castling' => $this->castling,
            'enPassant' => $this->enPassant,
            'halfmoveClock' => $this->halfmoveClock,
            'fullmoveNumber' => $this->fullmoveNumber,
        ];

        $from = $move['from'];
        $to = $move['to'];
        $piece = $this->board[$from];
        $captured = $this->board[$to] ?? null;

        // En passant capture removes the pawn beside the capturing pawn.
        if ($piece['type'] === 'p' && $to === $this->enPassant && $captured === null) {
            $captureSquare = $this->square($this->fileOf($to), $this->rankOf($from));
            $captured = $this->board[$captureSquare] ?? null;
            unset($this->board[$captureSquare]);
        }

        unset($this->board[$from]);
        $this->board[$to] = $piece;

        if ($move['promotion'] ?? null) {
            $this->board[$to]['type'] = $move['promotion'];
        }

        $this->updateCastlingRights($move, $captured);
        $this->enPassant = $this->computeEnPassant($from, $to, $piece);
        $this->halfmoveClock = ($piece['type'] === 'p' || $captured !== null) ? 0 : $this->halfmoveClock + 1;

        if ($piece['color'] === self::BLACK) {
            $this->fullmoveNumber++;
        }
        $this->turn = $this->opponent($this->turn);

        return $snapshot;
    }

    /**
     * Restore a snapshot produced by `applyState()`.
     *
     * @param array<string, mixed> $snapshot The snapshot.
     * @return void
     */
    protected function restoreState(array $snapshot): void
    {
        $this->board = $snapshot['board'];
        $this->turn = $snapshot['turn'];
        $this->castling = $snapshot['castling'];
        $this->enPassant = $snapshot['enPassant'];
        $this->halfmoveClock = $snapshot['halfmoveClock'];
        $this->fullmoveNumber = $snapshot['fullmoveNumber'];
    }

    /**
     * Determine whether a pseudo-legal move leaves the king in check.
     *
     * @param array<string, mixed> $move The move.
     * @return bool
     */
    protected function isLegal(array $move): bool
    {
        if (($move['castle'] ?? null) !== null) {
            return $this->isCastlingLegal($move);
        }

        $snapshot = $this->applyState($move);

        // After the move the turn has flipped, so `$this->turn` is the attacker
        // and `opponent($this->turn)` is the side that just moved.
        $king = $this->findKing($this->opponent($this->turn));
        $legal = $king === null || !$this->isAttacked($king, $this->turn);
        $this->restoreState($snapshot);

        return $legal;
    }

    /**
     * Validate the castling requirements beyond the base rules.
     *
     * @param array<string, mixed> $move The castling move.
     * @return bool
     */
    protected function isCastlingLegal(array $move): bool
    {
        $color = $this->turn;
        $enemy = $this->opponent($color);
        $rank = $color === self::WHITE ? 1 : 8;
        $kingSquare = $this->square(4, $rank);

        if ($this->isAttacked($kingSquare, $enemy)) {
            return false;
        }

        if ($move['castle'] === 'K') {
            $path = [$this->square(5, $rank), $this->square(6, $rank)];
        } else {
            $path = [$this->square(3, $rank), $this->square(2, $rank)];
        }

        foreach ($path as $square) {
            if ($this->isAttacked($square, $enemy)) {
                return false;
            }
        }

        $snapshot = $this->applyState($move);
        $king = $this->findKing($color);
        $legal = $king !== null && !$this->isAttacked($king, $enemy);
        $this->restoreState($snapshot);

        return $legal;
    }

    /**
     * Generate pseudo-legal moves for a piece.
     *
     * @param string $from Origin square.
     * @param array{type: string, color: string} $piece The piece.
     * @return array<int, array<string, mixed>>
     */
    protected function generateMoves(string $from, array $piece): array
    {
        return match ($piece['type']) {
            'p' => $this->generatePawnMoves($from, $piece['color']),
            'n' => $this->generateStepMoves($from, $piece['color'], self::KNIGHT_OFFSETS),
            'b' => $this->generateSliderMoves($from, $piece['color'], self::BISHOP_DIRECTIONS),
            'r' => $this->generateSliderMoves($from, $piece['color'], self::ROOK_DIRECTIONS),
            'q' => $this->generateSliderMoves($from, $piece['color'], self::QUEEN_DIRECTIONS),
            'k' => $this->generateKingMoves($from, $piece['color']),
            default => [],
        };
    }

    /**
     * Pawn moves including captures, en passant and promotion.
     *
     * @param string $from Origin square.
     * @param string $color Pawn colour.
     * @return array<int, array<string, mixed>>
     */
    protected function generatePawnMoves(string $from, string $color): array
    {
        $moves = [];
        $direction = $color === self::WHITE ? 1 : -1;
        $startRank = $color === self::WHITE ? 2 : 7;
        $promotionRank = $color === self::WHITE ? 8 : 1;
        $file = $this->fileOf($from);
        $rank = $this->rankOf($from);

        $forward = $this->square($file, $rank + $direction);
        if ($this->inBounds($file, $rank + $direction) && !isset($this->board[$forward])) {
            if ($rank + $direction === $promotionRank) {
                foreach ($this->buildPromotions($from, $forward) as $promotion) {
                    $moves[] = $promotion;
                }
            } else {
                $moves[] = $this->buildMove($from, $forward, 'p');
            }
            if ($rank === $startRank) {
                $double = $this->square($file, $rank + 2 * $direction);
                if (!isset($this->board[$double])) {
                    $moves[] = $this->buildMove($from, $double, 'p');
                }
            }
        }

        foreach ([-1, 1] as $offset) {
            $captureFile = $file + $offset;
            if (!$this->inBounds($captureFile, $rank + $direction)) {
                continue;
            }
            $target = $this->square($captureFile, $rank + $direction);
            $targetPiece = $this->board[$target] ?? null;
            if ($targetPiece !== null && $targetPiece['color'] !== $color) {
                if ($rank + $direction === $promotionRank) {
                    foreach ($this->buildPromotions($from, $target) as $promotion) {
                        $moves[] = $promotion;
                    }
                } else {
                    $moves[] = $this->buildMove($from, $target, 'p');
                }

                continue;
            }
            if ($targetPiece === null && $target === $this->enPassant) {
                $moves[] = $this->buildMove($from, $target, 'p');
            }
        }

        return $moves;
    }

    /**
     * King moves plus castling candidates.
     *
     * @param string $from Origin square.
     * @param string $color King colour.
     * @return array<int, array<string, mixed>>
     */
    protected function generateKingMoves(string $from, string $color): array
    {
        $moves = $this->generateStepMoves($from, $color, self::KING_OFFSETS);
        $rank = $color === self::WHITE ? 1 : 8;
        if ($from !== $this->square(4, $rank)) {
            return $moves;
        }

        $kingSide = $color === self::WHITE ? 'K' : 'k';
        $queenSide = $color === self::WHITE ? 'Q' : 'q';

        if (str_contains($this->castling, $kingSide)) {
            if (!isset($this->board[$this->square(5, $rank)]) && !isset($this->board[$this->square(6, $rank)])) {
                $move = $this->buildMove($from, $this->square(6, $rank), 'k');
                $move['castle'] = 'K';
                $moves[] = $move;
            }
        }
        if (str_contains($this->castling, $queenSide)) {
            $squares = [$this->square(1, $rank), $this->square(2, $rank), $this->square(3, $rank)];
            $empty = true;
            foreach ($squares as $square) {
                if (isset($this->board[$square])) {
                    $empty = false;

                    break;
                }
            }
            if ($empty) {
                $move = $this->buildMove($from, $this->square(2, $rank), 'k');
                $move['castle'] = 'Q';
                $moves[] = $move;
            }
        }

        return $moves;
    }

    /**
     * Moves for a piece that steps a fixed offset (knight, king).
     *
     * @param string $from Origin square.
     * @param string $color Piece colour.
     * @param array<int, array{int, int}> $offsets File/rank offsets.
     * @return array<int, array<string, mixed>>
     */
    protected function generateStepMoves(string $from, string $color, array $offsets): array
    {
        $moves = [];
        $file = $this->fileOf($from);
        $rank = $this->rankOf($from);

        foreach ($offsets as [$fileOffset, $rankOffset]) {
            $targetFile = $file + $fileOffset;
            $targetRank = $rank + $rankOffset;
            if (!$this->inBounds($targetFile, $targetRank)) {
                continue;
            }
            $target = $this->square($targetFile, $targetRank);
            $piece = $this->board[$target] ?? null;
            if ($piece !== null && $piece['color'] === $color) {
                continue;
            }
            $moves[] = $this->buildMove($from, $target, $this->board[$from]['type']);
        }

        return $moves;
    }

    /**
     * Moves for sliding pieces (bishop, rook, queen).
     *
     * @param string $from Origin square.
     * @param string $color Piece colour.
     * @param array<int, array{int, int}> $directions File/rank directions.
     * @return array<int, array<string, mixed>>
     */
    protected function generateSliderMoves(string $from, string $color, array $directions): array
    {
        $moves = [];
        $file = $this->fileOf($from);
        $rank = $this->rankOf($from);

        foreach ($directions as [$fileOffset, $rankOffset]) {
            $targetFile = $file + $fileOffset;
            $targetRank = $rank + $rankOffset;
            while ($this->inBounds($targetFile, $targetRank)) {
                $target = $this->square($targetFile, $targetRank);
                $piece = $this->board[$target] ?? null;
                if ($piece === null) {
                    $moves[] = $this->buildMove($from, $target, $this->board[$from]['type']);
                } else {
                    if ($piece['color'] !== $color) {
                        $moves[] = $this->buildMove($from, $target, $this->board[$from]['type']);
                    }

                    break;
                }
                $targetFile += $fileOffset;
                $targetRank += $rankOffset;
            }
        }

        return $moves;
    }

    /**
     * Build the move payload shared by all generators.
     *
     * @param string $from Origin square.
     * @param string $to Destination square.
     * @param string $type Piece type.
     * @param bool $promotable Whether the move may promote.
     * @return array<string, mixed>
     */
    protected function buildMove(string $from, string $to, string $type): array
    {
        $capture = isset($this->board[$to]);
        if (!$capture && $type === 'p' && $to === $this->enPassant) {
            $capture = true;
        }

        return [
            'from' => $from,
            'to' => $to,
            'type' => $type,
            'promotion' => null,
            'capture' => $capture,
        ];
    }

    /**
     * Build the promotion variants of a move.
     *
     * @param string $from Origin square.
     * @param string $to Destination square.
     * @return array<int, array<string, mixed>>
     */
    protected function buildPromotions(string $from, string $to): array
    {
        $moves = [];
        foreach (['q', 'r', 'b', 'n'] as $promotion) {
            $move = $this->buildMove($from, $to, 'p');
            $move['promotion'] = $promotion;
            $moves[] = $move;
        }

        return $moves;
    }

    /**
     * Whether a square is attacked by the given colour.
     *
     * @param string $square Target square.
     * @param string $color Attacking colour.
     * @return bool
     */
    protected function isAttacked(string $square, string $color): bool
    {
        $file = $this->fileOf($square);
        $rank = $this->rankOf($square);

        // Pawns.
        $pawnRank = $color === self::WHITE ? $rank - 1 : $rank + 1;
        foreach ([-1, 1] as $offset) {
            $pawnFile = $file + $offset;
            if (!$this->inBounds($pawnFile, $pawnRank)) {
                continue;
            }
            $piece = $this->board[$this->square($pawnFile, $pawnRank)] ?? null;
            if ($piece !== null && $piece['color'] === $color && $piece['type'] === 'p') {
                return true;
            }
        }

        // Knights.
        foreach (self::KNIGHT_OFFSETS as [$fileOffset, $rankOffset]) {
            $targetFile = $file + $fileOffset;
            $targetRank = $rank + $rankOffset;
            if (!$this->inBounds($targetFile, $targetRank)) {
                continue;
            }
            $piece = $this->board[$this->square($targetFile, $targetRank)] ?? null;
            if ($piece !== null && $piece['color'] === $color && $piece['type'] === 'n') {
                return true;
            }
        }

        // King.
        foreach (self::KING_OFFSETS as [$fileOffset, $rankOffset]) {
            $targetFile = $file + $fileOffset;
            $targetRank = $rank + $rankOffset;
            if (!$this->inBounds($targetFile, $targetRank)) {
                continue;
            }
            $piece = $this->board[$this->square($targetFile, $targetRank)] ?? null;
            if ($piece !== null && $piece['color'] === $color && $piece['type'] === 'k') {
                return true;
            }
        }

        // Sliding pieces.
        foreach (self::QUEEN_DIRECTIONS as [$fileOffset, $rankOffset]) {
            $isDiagonal = $fileOffset !== 0 && $rankOffset !== 0;
            $targetFile = $file + $fileOffset;
            $targetRank = $rank + $rankOffset;
            while ($this->inBounds($targetFile, $targetRank)) {
                $piece = $this->board[$this->square($targetFile, $targetRank)] ?? null;
                if ($piece !== null) {
                    if ($piece['color'] === $color) {
                        if ($piece['type'] === 'q' || ($isDiagonal && $piece['type'] === 'b') || (!$isDiagonal && $piece['type'] === 'r')) {
                            return true;
                        }
                    }

                    break;
                }
                $targetFile += $fileOffset;
                $targetRank += $rankOffset;
            }
        }

        return false;
    }

    /**
     * Find the king square for a colour.
     *
     * @param string $color King colour.
     * @return string|null
     */
    protected function findKing(string $color): ?string
    {
        foreach ($this->board as $square => $piece) {
            if ($piece['type'] === 'k' && $piece['color'] === $color) {
                return $square;
            }
        }

        return null;
    }

    /**
     * Update castling rights after a move.
     *
     * @param array<string, mixed> $move The move.
     * @param array{type: string, color: string}|null $captured Captured piece.
     * @return void
     */
    protected function updateCastlingRights(array $move, ?array $captured): void
    {
        if ($captured !== null && $captured['type'] === 'r') {
            $this->castling = str_replace($this->rookRight($move['to']), '', $this->castling);
        }

        if ($move['type'] === 'k') {
            if ($move['from'] === 'e1') {
                $this->castling = str_replace(['K', 'Q'], '', $this->castling);
            }
            if ($move['from'] === 'e8') {
                $this->castling = str_replace(['k', 'q'], '', $this->castling);
            }
        }

        if ($move['from'] === 'a1' || $move['to'] === 'a1') {
            $this->castling = str_replace('Q', '', $this->castling);
        }
        if ($move['from'] === 'h1' || $move['to'] === 'h1') {
            $this->castling = str_replace('K', '', $this->castling);
        }
        if ($move['from'] === 'a8' || $move['to'] === 'a8') {
            $this->castling = str_replace('q', '', $this->castling);
        }
        if ($move['from'] === 'h8' || $move['to'] === 'h8') {
            $this->castling = str_replace('k', '', $this->castling);
        }
    }

    /**
     * The castling right that a rook on the given square holds.
     *
     * @param string $square The square the rook stood on.
     * @return string Empty string when the square holds no castling rook.
     */
    protected function rookRight(string $square): string
    {
        return [
            'a1' => 'Q',
            'h1' => 'K',
            'a8' => 'q',
            'h8' => 'k',
        ][$square] ?? '';
    }

    /**
     * Compute the en passant target square for a move.
     *
     * @param string $from Origin square.
     * @param string $to Destination square.
     * @param array{type: string, color: string} $piece The moving piece.
     * @return string|null
     */
    protected function computeEnPassant(string $from, string $to, array $piece): ?string
    {
        if ($piece['type'] !== 'p') {
            return null;
        }
        if (abs($this->rankOf($to) - $this->rankOf($from)) !== 2) {
            return null;
        }

        return $this->square($this->fileOf($from), (int)(($this->rankOf($from) + $this->rankOf($to)) / 2));
    }

    /**
     * Build the SAN representation of a move.
     *
     * @param array<string, mixed> $move The move.
     * @return string
     */
    protected function buildSan(array $move, string $disambiguation = ''): string
    {
        if (($move['castle'] ?? null) !== null) {
            $san = $move['castle'] === 'K' ? 'O-O' : 'O-O-O';
        } else {
            $san = '';
            if ($move['type'] !== 'p') {
                $san = strtoupper($move['type']);
            }

            $disambiguation = $this->disambiguation($move);
            if ($move['type'] === 'p') {
                if ($move['capture']) {
                    $san .= $move['from'][0] . 'x';
                }
            } else {
                $san .= $disambiguation;
            }

            $san .= $move['to'];
            if (($move['promotion'] ?? null) !== null) {
                $san .= '=' . strtoupper($move['promotion']);
            }
        }

        if ($this->isCheck()) {
            $san .= $this->isCheckmate() ? '#' : '+';
        }

        return $san;
    }

    /**
     * Compute the disambiguation suffix for a move.
     *
     * @param array<string, mixed> $move The move.
     * @return string
     */
    protected function disambiguation(array $move): string
    {
        $candidates = [];
        foreach ($this->legalMoves() as $candidate) {
            if ($candidate['to'] !== $move['to'] || $candidate['from'] === $move['from']) {
                continue;
            }
            if ($candidate['type'] !== $move['type']) {
                continue;
            }
            $candidates[] = $candidate['from'];
        }

        if ($candidates === []) {
            return '';
        }

        $sameFile = false;
        $sameRank = false;
        foreach ($candidates as $candidate) {
            if ($candidate[0] === $move['from'][0]) {
                $sameFile = true;
            }
            if ($candidate[1] === $move['from'][1]) {
                $sameRank = true;
            }
        }

        if (!$sameFile) {
            return $move['from'][0];
        }
        if (!$sameRank) {
            return $move['from'][1];
        }

        return $move['from'];
    }

    /**
     * Whether the position has been repeated three times.
     */
    public function isThreefold(): bool
    {
        $counts = array_count_values(array_map(
            fn (string $key): string => $this->repetitionKey($key),
            $this->positionKeys,
        ));

        return max($counts) >= 3;
    }

    /**
     * A hashable key for the current position, used for repetition detection.
     */
    protected function positionKey(): string
    {
        return $this->repetitionKey($this->toFen());
    }

    /**
     * Strip the move counters from a FEN so positions compare equal.
     *
     * @param string $fen FEN string.
     * @return string
     */
    protected function repetitionKey(string $fen): string
    {
        $parts = explode(' ', $fen);

        return implode(' ', array_slice($parts, 0, 4));
    }

    /**
     * The opposing colour.
     *
     * @param string $color Colour.
     * @return string
     */
    protected function opponent(string $color): string
    {
        return $color === self::WHITE ? self::BLACK : self::WHITE;
    }

    /**
     * Convert a file/rank pair into an algebraic square.
     *
     * @param int $file File index (0 = a).
     * @param int $rank Rank (1 - 8).
     * @return string
     */
    protected function square(int $file, int $rank): string
    {
        return chr(97 + $file) . (string)$rank;
    }

    /**
     * File index of a square.
     *
     * @param string $square Algebraic square.
     * @return int
     */
    protected function fileOf(string $square): int
    {
        return ord($square[0]) - 97;
    }

    /**
     * Rank of a square.
     *
     * @param string $square Algebraic square.
     * @return int
     */
    protected function rankOf(string $square): int
    {
        return (int)$square[1];
    }

    /**
     * Whether a file/rank pair is on the board.
     *
     * @param int $file File index.
     * @param int $rank Rank.
     * @return bool
     */
    protected function inBounds(int $file, int $rank): bool
    {
        return $file >= 0 && $file <= 7 && $rank >= 1 && $rank <= 8;
    }

    /**
     * Knight move offsets.
     *
     * @var array<int, array{int, int}>
     */
    protected const KNIGHT_OFFSETS = [
        [1, 2], [2, 1], [2, -1], [1, -2],
        [-1, -2], [-2, -1], [-2, 1], [-1, 2],
    ];

    /**
     * King move offsets.
     *
     * @var array<int, array{int, int}>
     */
    protected const KING_OFFSETS = [
        [1, 0], [1, 1], [0, 1], [-1, 1],
        [-1, 0], [-1, -1], [0, -1], [1, -1],
    ];

    /**
     * Diagonal directions.
     *
     * @var array<int, array{int, int}>
     */
    protected const BISHOP_DIRECTIONS = [
        [1, 1], [1, -1], [-1, 1], [-1, -1],
    ];

    /**
     * Orthogonal directions.
     *
     * @var array<int, array{int, int}>
     */
    protected const ROOK_DIRECTIONS = [
        [1, 0], [-1, 0], [0, 1], [0, -1],
    ];

    /**
     * All directions.
     *
     * @var array<int, array{int, int}>
     */
    protected const QUEEN_DIRECTIONS = [
        [1, 0], [-1, 0], [0, 1], [0, -1],
        [1, 1], [1, -1], [-1, 1], [-1, -1],
    ];
}
