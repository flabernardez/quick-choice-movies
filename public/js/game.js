(function($) {
    'use strict';

    class QuickChoiceGame {
        constructor(container) {
            this.container = container;
            this.choiceListId = parseInt(container.dataset.choiceListId);
            this.allItems = [];
            this.remainingItems = [];
            this.currentPair = [];
            this.chosenPosition = null; // null, 0 (left), or 1 (right)
            this.currentChosen = null; // The currently chosen item
            this.gameId = `qcm_game_${this.choiceListId}`;

            this.init();
        }

        init() {
            this.loadGame();
        }

        async loadGame() {
            const savedState = this.loadFromCookie();

            if (savedState) {
                this.allItems = savedState.allItems;
                this.remainingItems = savedState.remainingItems;
                this.renderGame();
            } else {
                await this.loadItems();
            }
        }

        async loadItems() {
            this.container.innerHTML = '<div class="qcm-loading">Loading...</div>';

            try {
                const response = await fetch(qcmGame.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'qcm_get_choices',
                        nonce: qcmGame.nonce,
                        post_id: this.choiceListId,
                    }),
                });

                const data = await response.json();

                if (data.success && data.data.items) {
                    this.allItems = data.data.items;
                    this.remainingItems = [...this.allItems];
                    this.shuffleArray(this.remainingItems);
                    this.renderGame();
                } else {
                    this.container.innerHTML = '<div class="qcm-loading">Error: ' + (data.data?.message || 'No choices found') + '</div>';
                }
            } catch (error) {
                this.container.innerHTML = '<div class="qcm-loading">Error: ' + error.message + '</div>';
            }
        }

        renderGame() {
            if (this.remainingItems.length === 0) {
                this.container.innerHTML = '<div class="qcm-loading">No items available.</div>';
                return;
            }

            if (this.remainingItems.length === 1) {
                this.renderWinner();
                return;
            }

            // If we have a chosen item, keep it in its position
            if (this.currentChosen && this.chosenPosition !== null) {
                // Get next opponent
                const nextOpponent = this.remainingItems.find(item => item.id !== this.currentChosen.id);

                if (!nextOpponent) {
                    this.renderWinner();
                    return;
                }

                // Keep chosen in same position
                if (this.chosenPosition === 0) {
                    this.currentPair = [this.currentChosen, nextOpponent];
                } else {
                    this.currentPair = [nextOpponent, this.currentChosen];
                }
            } else {
                // First comparison - just use first two items
                this.currentPair = [
                    this.remainingItems[0],
                    this.remainingItems[1]
                ];
            }

            const html = `
                <div class="qcm-game-container">
                    <div class="qcm-game-choices">
                        ${this.renderChoice(this.currentPair[0], 0)}
                        ${this.renderChoice(this.currentPair[1], 1)}
                    </div>
                    <div class="qcm-game-controls">
                        <button class="qcm-button qcm-button--secondary qcm-reset-button">
                            Comienza de nuevo
                        </button>
                        <button class="qcm-button qcm-button--primary qcm-save-button">
                            Guardar
                        </button>
                    </div>
                </div>
            `;

            this.container.innerHTML = html;
            this.attachEventListeners();
        }

        renderChoice(item, index) {
            return `
                <div class="qcm-choice" data-index="${index}">
                    <img src="${item.image}" alt="${item.title}" class="qcm-choice__image">
                    <div class="qcm-choice__title">${item.title}</div>
                </div>
            `;
        }

        renderWinner() {
            const winner = this.remainingItems[0];

            const html = `
                <div class="qcm-winner">
                    <div class="qcm-winner__choice">
                        <img src="${winner.image}" alt="${winner.title}">
                    </div>
                    <h3>${winner.title}</h3>
                    <h2 class="qcm-winner__title">🏆 ¡Ganadora! 🏆</h2>
                    <div class="qcm-game-controls">
                        <button class="qcm-button qcm-button--primary qcm-reset-button">
                            Jugar de nuevo
                        </button>
                    </div>
                </div>
            `;

            this.container.innerHTML = html;
            this.attachEventListeners();
            this.clearCookie();
        }

        attachEventListeners() {
            const choices = this.container.querySelectorAll('.qcm-choice');
            choices.forEach((choice, index) => {
                choice.addEventListener('click', () => this.handleChoice(index));
            });

            const resetButton = this.container.querySelector('.qcm-reset-button');
            if (resetButton) {
                resetButton.addEventListener('click', () => this.resetGame());
            }

            const saveButton = this.container.querySelector('.qcm-save-button');
            if (saveButton) {
                saveButton.addEventListener('click', () => this.saveGame());
            }
        }

        handleChoice(chosenIndex) {
            const chosen = this.currentPair[chosenIndex];
            const notChosen = this.currentPair[chosenIndex === 0 ? 1 : 0];

            console.log('🎯 Clicked:', chosenIndex === 0 ? 'LEFT' : 'RIGHT');
            console.log('✅ Stays:', chosen.title);
            console.log('❌ Removed:', notChosen.title);

            // Store chosen item and its position
            this.currentChosen = chosen;
            this.chosenPosition = chosenIndex;

            console.log('📍 Position locked:', chosenIndex === 0 ? 'LEFT' : 'RIGHT');

            // Remove the NOT chosen
            this.remainingItems = this.remainingItems.filter(
                item => item.id !== notChosen.id
            );

            console.log('📊 Remaining:', this.remainingItems.length);

            // Check winner
            if (this.remainingItems.length === 1) {
                this.renderWinner();
                return;
            }

            // Render next comparison (renderGame will handle positioning)
            this.renderGame();
        }

        resetGame() {
            this.remainingItems = [...this.allItems];
            this.shuffleArray(this.remainingItems);
            this.currentChosen = null;
            this.chosenPosition = null;
            this.clearCookie();
            this.renderGame();
        }

        saveGame() {
            this.saveToCookie();
            const saveButton = this.container.querySelector('.qcm-save-button');
            if (saveButton) {
                const originalText = saveButton.textContent;
                saveButton.textContent = 'Guardado!';
                saveButton.disabled = true;
                setTimeout(() => {
                    saveButton.textContent = originalText;
                    saveButton.disabled = false;
                }, 2000);
            }
        }

        saveToCookie() {
            const state = {
                allItems: this.allItems,
                remainingItems: this.remainingItems,
            };
            const expirationDays = parseInt(qcmGame.cookieExpiration);
            const date = new Date();
            date.setTime(date.getTime() + (expirationDays * 24 * 60 * 60 * 1000));
            const expires = 'expires=' + date.toUTCString();
            document.cookie = `${this.gameId}=${encodeURIComponent(JSON.stringify(state))};${expires};path=/`;
        }

        loadFromCookie() {
            const name = this.gameId + '=';
            const decodedCookie = decodeURIComponent(document.cookie);
            const cookies = decodedCookie.split(';');

            for (let i = 0; i < cookies.length; i++) {
                let cookie = cookies[i];
                while (cookie.charAt(0) === ' ') {
                    cookie = cookie.substring(1);
                }
                if (cookie.indexOf(name) === 0) {
                    try {
                        return JSON.parse(cookie.substring(name.length, cookie.length));
                    } catch (e) {
                        return null;
                    }
                }
            }
            return null;
        }

        clearCookie() {
            document.cookie = `${this.gameId}=;expires=Thu, 01 Jan 1970 00:00:00 UTC;path=/`;
        }

        shuffleArray(array) {
            for (let i = array.length - 1; i > 0; i--) {
                const j = Math.floor(Math.random() * (i + 1));
                [array[i], array[j]] = [array[j], array[i]];
            }
        }
    }

    $(document).ready(function() {
        $('.qcm-game-block').each(function() {
            new QuickChoiceGame(this);
        });
    });

})(jQuery);
