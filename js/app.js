(function() {
    'use strict';

    const GRID_SIZE = 16;
    const INITIAL_SPEED_MS = 160;
    const MIN_SPEED_MS = 80;
    const SPEED_STEP_MS = 6;
    const BEST_SCORE_KEY = 'snake_best_score';

    const DIRECTIONS = {
        up: { x: 0, y: -1 },
        down: { x: 0, y: 1 },
        left: { x: -1, y: 0 },
        right: { x: 1, y: 0 },
    };

    class SnakeGame {
        constructor(onChange, onGameOver) {
            this.onChange = onChange;
            this.onGameOver = onGameOver;
            this.reset();
        }

        reset() {
            const mid = Math.floor(GRID_SIZE / 2);
            this.snake = [
                { x: mid - 1, y: mid },
                { x: mid, y: mid },
                { x: mid + 1, y: mid },
            ];
            this.direction = 'right';
            this.nextDirection = 'right';
            this.food = this.spawnFood();
            this.score = 0;
            this.speedMs = INITIAL_SPEED_MS;
            this.running = false;
            this.gameOver = false;
            this.loopId = null;
            this.notify();
        }

        getState() {
            return {
                snake: [...this.snake],
                food: this.food,
                score: this.score,
                length: this.snake.length,
                speedMs: this.speedMs,
                running: this.running,
                gameOver: this.gameOver,
            };
        }

        start() {
            if (this.running) return;
            if (this.gameOver) {
                this.reset();
            }
            this.running = true;
            this.loop();
            this.notify();
        }

        restart() {
            this.pause();
            this.reset();
            this.running = true;
            this.loop();
            this.notify();
        }

        pause() {
            if (this.loopId) {
                clearInterval(this.loopId);
                this.loopId = null;
            }
            this.running = false;
            this.notify();
        }

        loop() {
            if (this.loopId) {
                clearInterval(this.loopId);
            }
            this.loopId = setInterval(() => this.tick(), this.speedMs);
        }

        changeDirection(direction) {
            if (!DIRECTIONS[direction]) return;
            const currentVector = DIRECTIONS[this.direction];
            const nextVector = DIRECTIONS[direction];

            // Prevent reversing into itself
            if (currentVector.x + nextVector.x === 0 && currentVector.y + nextVector.y === 0) {
                return;
            }

            this.nextDirection = direction;
        }

        tick() {
            if (!this.running) return;

            this.direction = this.nextDirection;
            const head = this.snake[this.snake.length - 1];
            const vector = DIRECTIONS[this.direction];
            const newHead = { x: head.x + vector.x, y: head.y + vector.y };

            const ateFood = this.food && newHead.x === this.food.x && newHead.y === this.food.y;
            if (this.isCollision(newHead, ateFood)) {
                this.handleGameOver();
                return;
            }
            this.snake.push(newHead);

            if (!ateFood) {
                this.snake.shift();
            } else {
                this.score += 10;
                this.speedMs = Math.max(MIN_SPEED_MS, this.speedMs - SPEED_STEP_MS);
                this.food = this.spawnFood();
                this.loop(); // restart loop to apply new speed
            }

            this.notify();
        }

        isCollision(pos, willGrow) {
            if (pos.x < 0 || pos.x >= GRID_SIZE || pos.y < 0 || pos.y >= GRID_SIZE) {
                return true;
            }
            const startIndex = willGrow ? 0 : 1; // allow moving into the current tail when it will move
            for (let i = startIndex; i < this.snake.length; i++) {
                const segment = this.snake[i];
                if (segment.x === pos.x && segment.y === pos.y) {
                    return true;
                }
            }
            return false;
        }

        spawnFood() {
            const freeCells = [];
            for (let y = 0; y < GRID_SIZE; y++) {
                for (let x = 0; x < GRID_SIZE; x++) {
                    if (!this.snake.some(segment => segment.x === x && segment.y === y)) {
                        freeCells.push({ x, y });
                    }
                }
            }
            if (freeCells.length === 0) {
                return null;
            }
            return freeCells[Math.floor(Math.random() * freeCells.length)];
        }

        handleGameOver() {
            this.gameOver = true;
            this.pause();
            if (typeof this.onGameOver === 'function') {
                this.onGameOver(this.getState());
            }
        }

        notify() {
            if (typeof this.onChange === 'function') {
                this.onChange(this.getState());
            }
        }
    }

    class SnakeUI {
        constructor() {
            this.boardEl = document.querySelector('[data-board]');
            this.overlayEl = document.querySelector('[data-overlay]');
            this.overlayTitle = document.querySelector('[data-overlay-title]');
            this.overlayText = document.querySelector('[data-overlay-text]');
            this.overlayAction = document.querySelector('[data-overlay-action]');
            this.scoreEl = document.querySelector('[data-score]');
            this.bestEl = document.querySelector('[data-best]');
            this.speedEl = document.querySelector('[data-speed]');
            this.lengthEl = document.querySelector('[data-length]');
            this.statusRegion = document.querySelector('[data-status-region]');
            this.startButtons = Array.from(document.querySelectorAll('[data-action="start"]'));

            this.bestScore = Number(localStorage.getItem(BEST_SCORE_KEY) || 0);
            this.cells = new Map();
            this.state = null;
            this.hasPlayed = false;

            this.boardEl.style.setProperty('--snake-size', GRID_SIZE);
            this.game = new SnakeGame(
                (state) => this.render(state),
                (state) => this.onGameOver(state)
            );

            this.initBoard();
            this.bindControls();
            this.render(this.game.getState());
            this.showOverlay('준비 완료', '시작 버튼을 눌러 주세요.', '시작');
            this.updateStartLabels('start');
        }

        initBoard() {
            this.boardEl.innerHTML = '';
            this.cells.clear();
            for (let y = 0; y < GRID_SIZE; y++) {
                for (let x = 0; x < GRID_SIZE; x++) {
                    const cell = document.createElement('div');
                    cell.className = 'snake__cell';
                    const key = `${x}-${y}`;
                    this.cells.set(key, cell);
                    this.boardEl.appendChild(cell);
                }
            }
        }

        bindControls() {
            document.querySelectorAll('[data-action="start"]').forEach(btn => {
                btn.addEventListener('click', () => this.handleStart());
            });
            document.querySelectorAll('[data-action="pause"]').forEach(btn => {
                btn.addEventListener('click', () => this.handlePauseToggle());
            });
            this.overlayAction?.addEventListener('click', () => this.handleStart());

            document.querySelectorAll('[data-direction]').forEach(btn => {
                btn.addEventListener('click', () => {
                    this.game.changeDirection(btn.dataset.direction);
                });
            });

            document.addEventListener('keydown', (e) => {
                const keyMap = {
                    ArrowUp: 'up',
                    ArrowDown: 'down',
                    ArrowLeft: 'left',
                    ArrowRight: 'right',
                    w: 'up',
                    s: 'down',
                    a: 'left',
                    d: 'right',
                    W: 'up',
                    S: 'down',
                    A: 'left',
                    D: 'right',
                };
                if (keyMap[e.key]) {
                    e.preventDefault();
                    this.game.changeDirection(keyMap[e.key]);
                }
                if (e.key === ' ' && !e.repeat) {
                    e.preventDefault();
                    this.handlePauseToggle();
                }
            });

            let startX = 0;
            let startY = 0;
            const SWIPE_THRESHOLD = 18;

            this.boardEl.addEventListener('touchstart', (e) => {
                const touch = e.touches[0];
                startX = touch.clientX;
                startY = touch.clientY;
            }, { passive: true });

            this.boardEl.addEventListener('touchmove', (e) => {
                e.preventDefault();
            }, { passive: false });

            this.boardEl.addEventListener('touchend', (e) => {
                const touch = e.changedTouches[0];
                const dx = touch.clientX - startX;
                const dy = touch.clientY - startY;
                if (Math.abs(dx) < SWIPE_THRESHOLD && Math.abs(dy) < SWIPE_THRESHOLD) {
                    return;
                }
                if (Math.abs(dx) > Math.abs(dy)) {
                    this.game.changeDirection(dx > 0 ? 'right' : 'left');
                } else {
                    this.game.changeDirection(dy > 0 ? 'down' : 'up');
                }
            });
        }

        handleStart() {
            this.game.restart();
            this.hideOverlay();
            if (!this.hasPlayed) {
                this.hasPlayed = true;
            }
            this.updateStartLabels('restart');
            this.announce('게임을 시작합니다.');
        }

        handlePauseToggle() {
            if (this.state?.running) {
                this.game.pause();
                this.showOverlay('일시정지', '계속하려면 다시 시작을 누르세요.');
                this.announce('게임이 일시정지되었습니다.');
            } else if (this.state?.gameOver) {
                this.handleStart();
            } else {
                this.game.start();
                this.hideOverlay();
                this.updateStartLabels('restart');
                this.announce('게임이 재개되었습니다.');
            }
        }

        onGameOver(state) {
            if (state.score > this.bestScore) {
                this.bestScore = state.score;
                localStorage.setItem(BEST_SCORE_KEY, String(this.bestScore));
            }
            this.showOverlay('게임 오버', '다시 시작 버튼을 눌러 새로 플레이하세요.', '다시 시작');
            this.updateStartLabels('restart');
            this.announce('게임 오버');
            this.render(state);
        }

        showOverlay(title, text, actionLabel = '다시 시작') {
            if (!this.overlayEl) return;
            this.overlayTitle.textContent = title;
            this.overlayText.textContent = text;
            if (this.overlayAction) {
                this.overlayAction.textContent = actionLabel;
            }
            this.overlayEl.hidden = false;
        }

        hideOverlay() {
            if (!this.overlayEl) return;
            this.overlayEl.hidden = true;
        }

        render(state) {
            this.state = state;
            if (!state) return;

            this.scoreEl.textContent = state.score;
            this.bestEl.textContent = this.bestScore;
            this.lengthEl.textContent = state.length;
            const fps = (1000 / state.speedMs).toFixed(1);
            this.speedEl.textContent = fps.replace('.0', '');

            // Reset cells
            this.cells.forEach(cell => {
                cell.className = 'snake__cell';
            });

            // Draw snake
            state.snake.forEach((segment, idx) => {
                const key = `${segment.x}-${segment.y}`;
                const cell = this.cells.get(key);
                if (!cell) return;
                const isHead = idx === state.snake.length - 1;
                cell.classList.add('snake__cell--snake');
                if (isHead) {
                    cell.classList.add('snake__cell--head');
                }
            });

            // Draw food
            if (state.food) {
                const foodKey = `${state.food.x}-${state.food.y}`;
                const foodCell = this.cells.get(foodKey);
                if (foodCell) {
                    foodCell.classList.add('snake__cell--food');
                }
            }
        }

        announce(message) {
            if (!this.statusRegion) return;
            this.statusRegion.textContent = message;
        }

        updateStartLabels(mode) {
            const label = !this.hasPlayed && mode !== 'restart' ? '시작' : '다시 시작';
            this.startButtons.forEach(btn => {
                btn.textContent = label;
            });
            if (this.overlayAction) {
                this.overlayAction.textContent = label;
            }
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => new SnakeUI());
    } else {
        new SnakeUI();
    }
})();
