/**
 * Chess Arena — interactive legal move engine.
 *
 * The browser keeps a chess.js instance in sync with the server. Every move is
 * validated locally for instant feedback and sent to the PHP engine, which is
 * the only authority that accepts it.
 */
(function () {
    'use strict';

    var FILES = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'];

    var GLYPHS = {
        w: { k: '♔', q: '♕', r: '♖', b: '♗', n: '♘', p: '♙' },
        b: { k: '♚', q: '♛', r: '♜', b: '♝', n: '♞', p: '♟' }
    };

    var config = window.ARENA_BOARD || {};
    var boardEl = document.getElementById('arena-board');
    if (!boardEl) {
        return;
    }

    var state = config.state || {};
    var flipped = !!config.flipped;
    var readOnly = !!config.readOnly;
    var game = new Chess(state.fen || undefined);

    var selected = null;
    var targets = [];
    var lastMove = null;
    var turnStartedAt = Date.now();
    var clock = {
        white: state.white_time_ms || 0,
        black: state.black_time_ms || 0
    };
    var gameOver = !!state.game_over;
    var pending = false;

    var el = {
        whiteClock: document.querySelector('[data-clock="w"]'),
        blackClock: document.querySelector('[data-clock="b"]'),
        whiteName: document.querySelector('[data-player="w"]'),
        blackName: document.querySelector('[data-player="b"]'),
        whiteCard: document.querySelector('[data-card="w"]'),
        blackCard: document.querySelector('[data-card="b"]'),
        moves: document.getElementById('arena-moves'),
        evalFill: document.getElementById('arena-eval-fill'),
        evalLabel: document.getElementById('arena-eval-label'),
        banner: document.getElementById('arena-banner'),
        status: document.getElementById('arena-status')
    };

    /* ------------------------------------------------------------------ */
    /* Rendering                                                           */
    /* ------------------------------------------------------------------ */

    function parseFen(fen) {
        var placement = (fen || '').split(' ')[0];
        var rows = placement.split('/');
        var map = {};

        rows.forEach(function (row, index) {
            var rank = 8 - index;
            var file = 0;
            for (var i = 0; i < row.length; i++) {
                var ch = row.charAt(i);
                if (/[1-8]/.test(ch)) {
                    file += parseInt(ch, 10);
                    continue;
                }
                map[FILES[file] + rank] = ch;
                file++;
            }
        });

        return map;
    }

    function squareColor(fileIndex, rankIndex) {
        return (fileIndex + rankIndex) % 2 === 0 ? 'light' : 'dark';
    }

    function buildBoard() {
        var pieces = parseFen(game.fen());
        var fragment = document.createDocumentFragment();

        boardEl.innerHTML = '';
        for (var rank = 8; rank >= 1; rank--) {
            for (var fileIndex = 0; fileIndex < 8; fileIndex++) {
                var file = FILES[flipped ? 7 - fileIndex : fileIndex];
                var rankIndex = 8 - rank;
                var square = file + rank;
                var code = pieces[square];

                var cell = document.createElement('div');
                cell.className = 'square ' + squareColor(flipped ? 7 - fileIndex : fileIndex, rankIndex);
                cell.dataset.square = square;

                if (code) {
                    var piece = document.createElement('span');
                    piece.className = 'piece is-' + (code === code.toUpperCase() ? 'white' : 'black');
                    piece.textContent = GLYPHS[code === code.toUpperCase() ? 'w' : 'b'][code.toLowerCase()];
                    cell.appendChild(piece);
                }

                if (!flipped) {
                    if (rank === 1) {
                        var rankLabel = document.createElement('span');
                        rankLabel.className = 'coord file';
                        rankLabel.textContent = file;
                        cell.appendChild(rankLabel);
                    }
                    if (fileIndex === 0) {
                        var fileLabel = document.createElement('span');
                        fileLabel.className = 'coord rank';
                        fileLabel.textContent = rank;
                        cell.appendChild(fileLabel);
                    }
                }

                fragment.appendChild(cell);
            }
        }

        boardEl.appendChild(fragment);
        highlightLastMove();
    }

    function highlightLastMove() {
        if (!lastMove) {
            return;
        }
        ['from', 'to'].forEach(function (key) {
            var cell = boardEl.querySelector('[data-square="' + lastMove[key] + '"]');
            if (cell) {
                cell.classList.add('is-last');
            }
        });
    }

    function clearHighlights() {
        boardEl.querySelectorAll('.is-selected, .is-target, .is-last, .is-check').forEach(function (cell) {
            cell.classList.remove('is-selected', 'is-target', 'is-last', 'is-check');
        });
    }

    function markCheck() {
        if (!game.inCheck()) {
            return;
        }
        var turn = game.turn();
        var board = parseFen(game.fen());
        for (var square in board) {
            if (board.hasOwnProperty(square) && board[square] === (turn === 'w' ? 'K' : 'k')) {
                var cell = boardEl.querySelector('[data-square="' + square + '"]');
                if (cell) {
                    cell.classList.add('is-check');
                }
            }
        }
    }

    /* ------------------------------------------------------------------ */
    /* Move selection                                                      */
    /* ------------------------------------------------------------------ */

    function legalTargetsFor(square) {
        var moves = game.moves({ square: square, verbose: true });
        var result = [];
        moves.forEach(function (move) {
            result.push({ square: move.to, capture: !!move.captured, promotion: move.promotion || null });
        });
        return result;
    }

    function onSquareClick(event) {
        var cell = event.target.closest('.square');
        if (!cell || readOnly || gameOver || pending) {
            return;
        }

        var square = cell.dataset.square;

        if (selected) {
            var target = targets.filter(function (item) {
                return item.square === square;
            })[0];

            if (target) {
                if (target.promotion) {
                    openPromotion(target);
                } else {
                    submitMove(selected, square, null);
                }

                return;
            }
        }

        var piece = game.get(square);
        if (!piece || piece.color !== game.turn()) {
            clearHighlights();
            selected = null;
            targets = [];

            return;
        }

        selected = square;
        targets = legalTargetsFor(square);
        clearHighlights();

        var origin = boardEl.querySelector('[data-square="' + square + '"]');
        if (origin) {
            origin.classList.add('is-selected');
        }

        targets.forEach(function (item) {
            var cellTarget = boardEl.querySelector('[data-square="' + item.square + '"]');
            if (cellTarget) {
                cellTarget.classList.add('is-target');
                if (item.capture) {
                    cellTarget.classList.add('is-capture');
                }
            }
        });
    }

    /* ------------------------------------------------------------------ */
    /* Promotion dialog                                                    */
    /* ------------------------------------------------------------------ */

    var promotionOverlay = document.getElementById('promotion-overlay');
    var promotionTarget = null;

    function openPromotion(target) {
        promotionTarget = target;
        if (!promotionOverlay) {
            submitMove(selected, target.square, 'q');

            return;
        }
        promotionOverlay.classList.add('is-open');
    }

    if (promotionOverlay) {
        promotionOverlay.querySelectorAll('[data-promotion]').forEach(function (button) {
            button.addEventListener('click', function () {
                var piece = button.dataset.promotion;
                promotionOverlay.classList.remove('is-open');
                if (selected && promotionTarget) {
                    submitMove(selected, promotionTarget.square, piece);
                }
            });
        });
    }

    /* ------------------------------------------------------------------ */
    /* Server communication                                                */
    /* ------------------------------------------------------------------ */

    function csrfToken() {
        var meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute('content') : '';
    }

    function elapsed() {
        if (!state.turn) {
            return 0;
        }

        return Math.min(Math.max(Date.now() - turnStartedAt, 0), 600000);
    }

    function submitMove(from, to, promotion) {
        var move = { from: from, to: to };
        if (promotion) {
            move.promotion = promotion;
        }

        pending = true;
        setStatus('Sending move…');

        fetch(config.moveUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                from: move.from,
                to: move.to,
                promotion: move.promotion || null,
                elapsed: elapsed()
            })
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                pending = false;
                setStatus(payload.message || '');
                if (payload.state) {
                    applyState(payload.state);
                }
            }, function () {
                pending = false;
                setStatus('The move could not be sent. Check your connection.');
            });
    }

    function postAction(url, message) {
        pending = true;
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-Token': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: '{}'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                pending = false;
                setStatus(payload.message || message);
                if (payload.state) {
                    applyState(payload.state);
                }
            }, function () {
                pending = false;
                setStatus('The request failed. Please try again.');
            });
    }

    function poll() {
        if (gameOver || pending || !config.stateUrl) {
            return;
        }

        fetch(config.stateUrl, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            cache: 'no-store'
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (payload) {
                if (!payload.state) {
                    return;
                }
                if (payload.state.fen !== state.fen) {
                    applyState(payload.state);
                } else {
                    clock.white = payload.state.white_time_ms;
                    clock.black = payload.state.black_time_ms;
                }
            })
            .catch(function () {
                /* the connection dropped, the next tick tries again */
            });
    }

    /* ------------------------------------------------------------------ */
    /* State handling                                                      */
    /* ------------------------------------------------------------------ */

    function applyState(next) {
        var previousFen = state.fen;
        state = next;

        if (next.fen && next.fen !== previousFen) {
            game = new Chess(next.fen);
            var moves = next.moves || [];
            if (moves.length) {
                lastMove = { from: moves[moves.length - 1].from, to: moves[moves.length - 1].to };
            }
        }

        clock.white = next.white_time_ms;
        clock.black = next.black_time_ms;
        gameOver = !!next.game_over;

        selected = null;
        targets = [];

        buildBoard();
        markCheck();
        renderMoves();
        renderEvaluation(next.evaluation);
        renderClocks();
        renderTurn(next.turn);
        renderBanner(next);
    }

    function renderMoves() {
        if (!el.moves) {
            return;
        }

        var moves = state.moves || [];
        var html = '';
        for (var i = 0; i < moves.length; i += 2) {
            html += '<span class="no">' + ((i / 2) + 1) + '.</span>';
            html += '<span class="san' + (i === moves.length - 1 ? ' is-latest' : '') + '">' + moves[i].san + '</span>';
            html += '<span class="san' + (i + 1 === moves.length - 1 ? ' is-latest' : '') + '">' +
                (moves[i + 1] ? moves[i + 1].san : '') + '</span>';
        }

        el.moves.innerHTML = html || '<span class="no"></span><span class="san muted">No moves yet</span><span class="san"></span>';
        el.moves.scrollTop = el.moves.scrollHeight;
    }

    function renderEvaluation(evaluation) {
        var value = typeof evaluation === 'number' ? evaluation : 0;
        var clamped = Math.max(-6, Math.min(6, value));
        var percent = 50 + clamped * 5;

        if (el.evalFill) {
            el.evalFill.style.height = percent + '%';
        }
        if (el.evalLabel) {
            el.evalLabel.textContent = (value > 0 ? '+' : '') + value.toFixed(1);
        }
    }

    function renderClocks() {
        if (el.whiteClock) {
            el.whiteClock.textContent = formatTime(clock.white);
            el.whiteClock.classList.toggle('is-running', state.turn === 'w' && !gameOver);
            el.whiteClock.classList.toggle('is-low', clock.white < 30000);
        }
        if (el.blackClock) {
            el.blackClock.textContent = formatTime(clock.black);
            el.blackClock.classList.toggle('is-running', state.turn === 'b' && !gameOver);
            el.blackClock.classList.toggle('is-low', clock.black < 30000);
        }
    }

    function renderTurn(turn) {
        if (el.whiteCard) {
            el.whiteCard.classList.toggle('is-turn', turn === 'w');
        }
        if (el.blackCard) {
            el.blackCard.classList.toggle('is-turn', turn === 'b');
        }
    }

    function renderBanner(next) {
        if (!el.banner) {
            return;
        }

        el.banner.className = 'game-banner';

        if (!next.result || next.result === 'Pending') {
            if (next.in_check) {
                el.banner.classList.add('is-open', 'is-draw');
                el.banner.textContent = 'Check!';
            }

            return;
        }

        el.banner.classList.add('is-open');
        var won = next.result === 'White_Win' ? config.whiteWins : next.result === 'Black_Win' ? config.blackWins : false;
        el.banner.classList.add(won ? 'is-win' : (next.result === 'Draw' ? 'is-draw' : 'is-loss'));
        el.banner.textContent = won
            ? 'You won ' + (next.termination ? '(' + next.termination.replace(/_/g, ' ') + ')' : '')
            : (next.result === 'Draw' ? 'The game ended in a draw.' : 'You lost this game.');
    }

    function setStatus(message) {
        if (el.status && message) {
            el.status.textContent = message;
        }
    }

    function formatTime(milliseconds) {
        var total = Math.max(0, Math.floor(milliseconds / 1000));
        var minutes = Math.floor(total / 60);
        var seconds = total % 60;

        return minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
    }

    /* ------------------------------------------------------------------ */
    /* Bootstrap                                                           */
    /* ------------------------------------------------------------------ */

    boardEl.addEventListener('click', onSquareClick);

    var resignButton = document.getElementById('arena-resign');
    if (resignButton && config.resignUrl) {
        resignButton.addEventListener('click', function () {
            if (window.confirm('Resign this game? The opponent wins immediately.')) {
                postAction(config.resignUrl, 'You resigned the game.');
            }
        });
    }

    var drawButton = document.getElementById('arena-draw');
    if (drawButton && config.drawUrl) {
        drawButton.addEventListener('click', function () {
            if (window.confirm('Agree a draw with the opponent?')) {
                postAction(config.drawUrl, 'The game ended in a draw.');
            }
        });
    }

    buildBoard();
    markCheck();
    renderMoves();
    renderEvaluation(state.evaluation);
    renderClocks();
    renderTurn(state.turn);
    renderBanner(state);

    window.setInterval(function () {
        if (!gameOver && state.turn) {
            var key = state.turn === 'w' ? 'white' : 'black';
            clock[key] = Math.max(0, clock[key] - 1000);
            renderClocks();
        }
    }, 1000);

    window.setInterval(poll, 2500);
})();
