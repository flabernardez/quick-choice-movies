(function($) {
    'use strict';

    class QuickChoiceGame {
        constructor(container) {
            this.container = container;
            this.choiceListId = parseInt(container.dataset.choiceListId);
            this.allItems = [];
            this.remainingItems = [];
            this.currentPair = [];
            this.gameId = `qcm_game_${this.choiceListId}`;

            this.init();
        }

        init() {
            this.loadGame();
        }

        async loadGame() {
            // Check if there's a saved game state
            const savedState = this.loadFromCookie();

            if (savedState) {
                this.allItems = savedState.allItems;
                this.remainingItems = savedState.remainingItems;
                this.renderGame();
            } else {
                // Load items from API
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
                    this.container.innerHTML = '<div class="qcm-loading">Error loading game.</div>';
                }
            } catch (error) {
                console.error('Error loading game:', error);
                this.container.innerHTML = '<div class="qcm-loading">Error loading game.</div>';
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

            this.currentPair = [
                this.remainingItems[0],
                this.remainingItems[1]
            ];

            const html = `
                <div class="qcm-game-container">
                    <div class="qcm-game-choices">
                        ${this.renderChoice(this.currentPair[0], 0)}
                        ${this.renderChoice(this.currentPair[1], 1)}
                    </div>
                    <div class="qcm-game-controls">
                        <button class="qcm-button qcm-button--secondary qcm-reset-button">
                            Reset Game
                        </button>
                        <button class="qcm-button qcm-button--primary qcm-save-button">
                            Save Progress
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
                    <h2 class="qcm-winner__title">🏆 Winner! 🏆</h2>
                    <div class="qcm-winner__choice">
                        <img src="${winner.image}" alt="${winner.title}">
                        <h3>${winner.title}</h3>
                    </div>
                    <div class="qcm-game-controls">
                        <button class="qcm-button qcm-button--primary qcm-reset-button">
                            Play Again
                        </button>
                    </div>
                </div>
            `;

            this.container.innerHTML = html;
            this.attachEventListeners();
            this.clearCookie();
        }

        attachEventListeners() {
            // Choice click handlers
            const choices = this.container.querySelectorAll('.qcm-choice');
            choices.forEach((choice, index) => {
                choice.addEventListener('click', () => this.handleChoice(index));
            });

            // Reset button
            const resetButton = this.container.querySelector('.qcm-reset-button');
            if (resetButton) {
                resetButton.addEventListener('click', () => this.resetGame());
            }

            // Save button
            const saveButton = this.container.querySelector('.qcm-save-button');
            if (saveButton) {
                saveButton.addEventListener('click', () => this.saveGame());
            }
        }

        handleChoice(chosenIndex) {
            const chosen = this.currentPair[chosenIndex];
            const notChosen = this.currentPair[chosenIndex === 0 ? 1 : 0];

            // Remove both from remaining items
            this.remainingItems = this.remainingItems.filter(
                item => item.id !== chosen.id && item.id !== notChosen.id
            );

            // Add chosen back to the end
            this.remainingItems.push(chosen);

            // Render next pair
            this.renderGame();
        }

        resetGame() {
            this.remainingItems = [...this.allItems];
            this.shuffleArray(this.remainingItems);
            this.clearCookie();
            this.renderGame();
        }

        saveGame() {
            this.saveToCookie();

            // Show feedback
            const saveButton = this.container.querySelector('.qcm-save-button');
            if (saveButton) {
                const originalText = saveButton.textContent;
                saveButton.textContent = 'Saved!';
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

    // Initialize all games on page
    $(document).ready(function() {
        $('.qcm-game-block').each(function() {
            new QuickChoiceGame(this);
        });
    });

})(jQuery);
