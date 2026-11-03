<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\MatchesTable;
use App\Service\Tournament\MatchService;
use Cake\Http\Response;

/**
 * The live arena: the board, the clocks and the move endpoint.
 */
class MatchesController extends AppController
{
    /**
     * @var \App\Model\Table\MatchesTable
     */
    protected $Matches;

    /**
     * @var \App\Service\Tournament\MatchService
     */
    protected $matchService;

    /**
     * Initialize method.
     *
     * @return void
     */
    public function initialize(): void
    {
        parent::initialize();

        $this->Matches = $this->getTableLocator()->get('Matches');
        $this->matchService = new MatchService();
    }

    /**
     * The board of a live match.
     *
     * @param string|null $id The match.
     * @return void
     */
    public function play(?string $id = null): void
    {
        $match = $this->loadMatch($id);
        $this->Authorization->authorize($match, 'view');

        $userId = $this->currentUserId();
        $isPlayer = $userId !== null && (
            (int)$match->get('white_player_id') === $userId
            || (int)$match->get('black_player_id') === $userId
        );

        $this->set([
            'match' => $match,
            'state' => $this->matchService->state($match),
            'isWhite' => (int)$match->get('white_player_id') === $userId,
            'canPlay' => $isPlayer && $match->get('status') === 'Live',
        ]);
    }

    /**
     * Play a move.
     *
     * @param string|null $id The match.
     * @return \Cake\Http\Response
     */
    public function move(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);

        $match = $this->loadMatch($id);
        $this->Authorization->authorize($match, 'play');

        $user = $this->currentUser();
        if ($user === null) {
            return $this->jsonResponse(['ok' => false, 'message' => 'Please sign in again.', 'state' => null]);
        }

        $data = $this->request->getData();
        $from = strtolower((string)($data['from'] ?? ''));
        $to = strtolower((string)($data['to'] ?? ''));
        $promotion = isset($data['promotion']) ? strtolower((string)$data['promotion']) : null;
        $elapsed = (int)($data['elapsed'] ?? 0);

        if (!preg_match('/^[a-h][1-8]$/', $from) || !preg_match('/^[a-h][1-8]$/', $to)) {
            return $this->jsonResponse([
                'ok' => false,
                'message' => 'That move is not legal in this position.',
                'state' => $this->matchService->state($match),
            ]);
        }

        $result = $this->matchService->play($match, $user, $from, $to, $promotion, $elapsed);

        return $this->jsonResponse($result);
    }

    /**
     * Concede the game.
     *
     * @param string|null $id The match.
     * @return \Cake\Http\Response
     */
    public function resign(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);

        $match = $this->loadMatch($id);
        $this->Authorization->authorize($match, 'play');

        $user = $this->currentUser();
        if ($user === null) {
            return $this->jsonResponse(['ok' => false, 'message' => 'Please sign in again.', 'state' => null]);
        }

        return $this->jsonResponse($this->matchService->resign($match, $user));
    }

    /**
     * Agree a draw.
     *
     * @param string|null $id The match.
     * @return \Cake\Http\Response
     */
    public function draw(?string $id = null): Response
    {
        $this->request->allowMethod(['post']);

        $match = $this->loadMatch($id);
        $this->Authorization->authorize($match, 'play');

        $user = $this->currentUser();
        if ($user === null) {
            return $this->jsonResponse(['ok' => false, 'message' => 'Please sign in again.', 'state' => null]);
        }

        return $this->jsonResponse($this->matchService->draw($match, $user));
    }

    /**
     * Poll the state of a match.
     *
     * @param string|null $id The match.
     * @return \Cake\Http\Response
     */
    public function state(?string $id = null): Response
    {
        $match = $this->loadMatch($id);
        $this->Authorization->authorize($match, 'view');

        return $this->jsonResponse([
            'ok' => true,
            'message' => '',
            'state' => $this->matchService->state($match),
        ]);
    }

    /**
     * Reconnect to a live match and return the current server state.
     * Used after a browser reload or connection drop so the player resumes
     * with the same position, same remaining time, and same turn.
     *
     * @param string|null $id The match.
     * @return \Cake\Http\Response
     */
    public function reconnect(?string $id = null): Response
    {
        $match = $this->loadMatch($id);
        $this->Authorization->authorize($match, 'view');

        $userId = $this->currentUserId();
        $isPlayer = $userId !== null && (
            (int)$match->get('white_player_id') === $userId
            || (int)$match->get('black_player_id') === $userId
        );

        if (!$isPlayer) {
            return $this->jsonResponse([
                'ok' => false,
                'message' => 'You are not a player of this game.',
                'state' => null,
            ]);
        }

        if ($match->get('status') !== 'Live') {
            return $this->jsonResponse([
                'ok' => false,
                'message' => 'This game is already finished.',
                'state' => $this->matchService->state($match),
            ]);
        }

        return $this->jsonResponse([
            'ok' => true,
            'message' => 'Reconnected. Position and clock restored from the server.',
            'state' => $this->matchService->state($match),
            'restored' => true,
        ]);
    }

    /**
     * The legal destinations of a square.
     *
     * @param string|null $id The match.
     * @return \Cake\Http\Response
     */
    public function targets(?string $id = null): Response
    {
        $match = $this->loadMatch($id);
        $this->Authorization->authorize($match, 'view');

        $square = strtolower((string)($this->request->getQuery('square') ?? ''));
        if (!preg_match('/^[a-h][1-8]$/', $square)) {
            return $this->jsonResponse(['ok' => false, 'targets' => []]);
        }

        return $this->jsonResponse([
            'ok' => true,
            'targets' => $this->matchService->legalTargets($match, $square),
        ]);
    }

    /**
     * Load a match with its players.
     *
     * @param string|null $id The match.
     * @return \App\Model\Entity\ArenaMatch
     */
    protected function loadMatch(?string $id): \App\Model\Entity\ArenaMatch
    {
        /** @var \App\Model\Table\MatchesTable $matches */
        $matches = $this->getTableLocator()->get('Matches');

        return $matches->get($id, [
            'contain' => ['Tournaments', 'WhitePlayers', 'BlackPlayers'],
        ]);
    }

    /**
     * Build a JSON response.
     *
     * @param array<string, mixed> $payload The payload.
     * @return \Cake\Http\Response
     */
    protected function jsonResponse(array $payload): Response
    {
        return $this->response
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload, JSON_THROW_ON_ERROR));
    }
}
