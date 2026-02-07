(function($) {
    'use strict';

    class TierListGame {
        constructor(container) {
            this.container = container;
            this.tierListId = parseInt(container.dataset.tierListId);
            this.items = [];
            this.tiers = [];
            this.rankings = {}; // { itemId: tierId }
            this.gameId = `qcm_tierlist_${this.tierListId}`;
            this.draggedItem = null;

            this.init();
        }

        async init() {
            const savedState = this.loadFromStorage();

            if (savedState) {
                this.items = savedState.items;
                this.tiers = savedState.tiers;
                this.rankings = savedState.rankings;
                this.render();
            } else {
                await this.loadData();
            }
        }

        async loadData() {
            this.container.innerHTML = '<div class="qcm-tierlist-loading">Cargando...</div>';

            try {
                const response = await fetch(qcmTierList.ajaxUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'qcm_get_tier_list',
                        nonce: qcmTierList.nonce,
                        post_id: this.tierListId,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.items = data.data.items || [];
                    this.tiers = data.data.tiers || [];
                    this.rankings = {};
                    this.render();
                } else {
                    this.container.innerHTML = '<div class="qcm-tierlist-loading">Error: ' + (data.data?.message || 'No se pudo cargar') + '</div>';
                }
            } catch (error) {
                this.container.innerHTML = '<div class="qcm-tierlist-loading">Error: ' + error.message + '</div>';
            }
        }

        render() {
            const html = `
                <div class="qcm-tierlist-container">
                    <div class="qcm-tierlist-tiers">
                        ${this.tiers.map(tier => this.renderTierRow(tier)).join('')}
                    </div>
                    <div class="qcm-tierlist-pool">
                        <div class="qcm-tierlist-pool__title">Items sin clasificar</div>
                        <div class="qcm-tierlist-pool__items" data-tier="unranked">
                            ${this.renderUnrankedItems()}
                        </div>
                    </div>
                    <div class="qcm-tierlist-controls">
                        <button class="qcm-tierlist-btn qcm-tierlist-btn--primary qcm-save-btn">
                            💾 Guardar
                        </button>
                        <button class="qcm-tierlist-btn qcm-tierlist-btn--secondary qcm-reset-btn">
                            🔄 Reiniciar
                        </button>
                        <button class="qcm-tierlist-btn qcm-tierlist-btn--secondary qcm-screenshot-btn">
                            📷 Captura
                        </button>
                    </div>
                </div>
            `;

            this.container.innerHTML = html;
            this.attachEventListeners();
        }

        renderTierRow(tier) {
            const tierItems = this.items.filter(item => this.rankings[item.id] === tier.id);

            return `
                <div class="qcm-tier-row">
                    <div class="qcm-tier-label" style="background-color: ${tier.color}">
                        ${tier.label}
                    </div>
                    <div class="qcm-tier-items" data-tier="${tier.id}">
                        ${tierItems.map(item => this.renderItem(item)).join('')}
                    </div>
                </div>
            `;
        }

        renderUnrankedItems() {
            const unrankedItems = this.items.filter(item => !this.rankings[item.id]);
            return unrankedItems.map(item => this.renderItem(item)).join('');
        }

        renderItem(item) {
            return `
                <div class="qcm-tierlist-item" data-item-id="${item.id}" draggable="true">
                    ${item.image ? `<img src="${item.image}" alt="${item.title}">` : ''}
                    <div class="qcm-tierlist-item__title">${item.title}</div>
                </div>
            `;
        }

        attachEventListeners() {
            // Drag and Drop
            const items = this.container.querySelectorAll('.qcm-tierlist-item');
            const dropZones = this.container.querySelectorAll('.qcm-tier-items, .qcm-tierlist-pool__items');

            items.forEach(item => {
                item.addEventListener('dragstart', (e) => this.handleDragStart(e));
                item.addEventListener('dragend', (e) => this.handleDragEnd(e));
            });

            dropZones.forEach(zone => {
                zone.addEventListener('dragover', (e) => this.handleDragOver(e));
                zone.addEventListener('dragleave', (e) => this.handleDragLeave(e));
                zone.addEventListener('drop', (e) => this.handleDrop(e));
            });

            // Buttons
            this.container.querySelector('.qcm-save-btn')?.addEventListener('click', () => this.save());
            this.container.querySelector('.qcm-reset-btn')?.addEventListener('click', () => this.reset());
            this.container.querySelector('.qcm-screenshot-btn')?.addEventListener('click', () => this.screenshot());
        }

        handleDragStart(e) {
            this.draggedItem = e.target;
            e.target.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', e.target.dataset.itemId);
        }

        handleDragEnd(e) {
            e.target.classList.remove('dragging');
            this.draggedItem = null;

            // Remove all drag-over classes
            this.container.querySelectorAll('.drag-over').forEach(el => {
                el.classList.remove('drag-over');
            });
        }

        handleDragOver(e) {
            e.preventDefault();
            e.dataTransfer.dropEffect = 'move';
            e.currentTarget.classList.add('drag-over');
        }

        handleDragLeave(e) {
            e.currentTarget.classList.remove('drag-over');
        }

        handleDrop(e) {
            e.preventDefault();
            e.currentTarget.classList.remove('drag-over');

            const itemId = e.dataTransfer.getData('text/plain');
            const tierId = e.currentTarget.dataset.tier;

            if (tierId === 'unranked') {
                delete this.rankings[itemId];
            } else {
                this.rankings[itemId] = tierId;
            }

            // Move the item element
            if (this.draggedItem) {
                e.currentTarget.appendChild(this.draggedItem);
            }

            // Auto-save
            this.saveToStorage();
        }

        save() {
            this.saveToStorage();

            const btn = this.container.querySelector('.qcm-save-btn');
            const originalText = btn.textContent;
            btn.textContent = '✓ Guardado!';
            btn.disabled = true;

            setTimeout(() => {
                btn.textContent = originalText;
                btn.disabled = false;
            }, 2000);
        }

        reset() {
            if (confirm('¿Estás seguro de que quieres reiniciar? Se perderá tu progreso.')) {
                this.rankings = {};
                this.clearStorage();
                this.render();
            }
        }

        screenshot() {
            const container = this.container.querySelector('.qcm-tierlist-container');
            container.classList.add('screenshot-mode');

            alert('Modo captura activado. Usa la herramienta de captura de tu sistema o navegador para guardar la imagen.');

            setTimeout(() => {
                container.classList.remove('screenshot-mode');
            }, 10000);
        }

        saveToStorage() {
            const state = {
                items: this.items,
                tiers: this.tiers,
                rankings: this.rankings,
            };
            const days = (qcmTierList && qcmTierList.cookieExpiration) ? parseInt(qcmTierList.cookieExpiration) : 30;
            const expires = new Date(Date.now() + days * 864e5).toUTCString();
            document.cookie = this.gameId + '=' + encodeURIComponent(JSON.stringify(state)) + ';expires=' + expires + ';path=/;SameSite=Lax';
        }

        loadFromStorage() {
            try {
                const match = document.cookie.split('; ').find(c => c.startsWith(this.gameId + '='));
                if (!match) return null;
                return JSON.parse(decodeURIComponent(match.split('=').slice(1).join('=')));
            } catch (e) {
                return null;
            }
        }

        clearStorage() {
            document.cookie = this.gameId + '=;expires=Thu, 01 Jan 1970 00:00:00 GMT;path=/;SameSite=Lax';
        }
    }

    // Initialize when DOM is ready
    $(document).ready(function() {
        $('.qcm-tierlist-block').each(function() {
            new TierListGame(this);
        });
    });

})(jQuery);
