import { Controller } from '@hotwired/stimulus';
import { getComponent } from '@symfony/ux-live-component';

export default class extends Controller {
    static targets = ['column', 'card'];

    async initialize()
    {
        this.component = await getComponent(this.element.closest('[data-controller~="live"]'));
    }

    connect()
    {
        this.dragged = null;
        this.element.addEventListener('dragstart', this.onDragStart);
        this.element.addEventListener('dragend', this.onDragEnd);
        this.columnTargets.forEach((column) => {
            column.addEventListener('dragover', this.onDragOver);
            column.addEventListener('drop', this.onDrop);
            column.addEventListener('dragleave', this.onDragLeave);
        });
    }

    disconnect()
    {
        this.element.removeEventListener('dragstart', this.onDragStart);
        this.element.removeEventListener('dragend', this.onDragEnd);
        this.columnTargets.forEach((column) => {
            column.removeEventListener('dragover', this.onDragOver);
            column.removeEventListener('drop', this.onDrop);
            column.removeEventListener('dragleave', this.onDragLeave);
        });
    }

    onDragStart = (event) => {
        const card = event.target.closest('[data-card-id]');
        if (!card) {
            return;
        }

        this.dragged = card;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.cardId);
        requestAnimationFrame(() => card.classList.add('amenity-card--dragging'));
    };

    onDragOver = (event) => {
        if (!this.dragged) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        const column = event.currentTarget;
        column.classList.add('amenity-column__cards--over');
        const after = this.cardAfterPointer(column, event.clientY);
        if (after === null) {
            column.appendChild(this.dragged);
        } else if (after !== this.dragged) {
            column.insertBefore(this.dragged, after);
        }
    };

    onDragLeave = (event) => {
        event.currentTarget.classList.remove('amenity-column__cards--over');
    };

    onDrop = async (event) => {
        event.preventDefault();
        const column = event.currentTarget;
        column.classList.remove('amenity-column__cards--over');
        if (!this.dragged) {
            return;
        }

        const cardId = parseInt(this.dragged.dataset.cardId, 10);
        const status = column.dataset.status;
        const position = [...column.querySelectorAll('[data-card-id]')].indexOf(this.dragged);
        this.cleanup();
        await this.component.action('moveCard', { cardId, status, position });
    };

    onDragEnd = () => {
        this.cleanup();
        this.columnTargets.forEach((column) => column.classList.remove('amenity-column__cards--over'));
    };

    cleanup()
    {
        if (this.dragged) {
            this.dragged.classList.remove('amenity-card--dragging');
            this.dragged = null;
        }
    }

    cardAfterPointer(column, y)
    {
        const cards = [...column.querySelectorAll('[data-card-id]:not(.amenity-card--dragging)')];
        for (const card of cards) {
            const rect = card.getBoundingClientRect();
            if (y < rect.top + rect.height / 2) {
                return card;
            }
        }

        return null;
    }
}
