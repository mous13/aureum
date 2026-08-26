import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['thread', 'count', 'empty', 'input', 'error'];
    static values = {
        url: String,
        cardId: Number,
        csrfToken: String,
        maxLength: Number,
        readOnly: Boolean,
    };

    connect()
    {
        this.editingId = null;
        this.load();
    }

    async load() {
        const comments = await this.request(this.urlValue, 'GET');
        if (comments !== null) {
            this.render(comments);
        }
    }

    async submit(event) {
        event.preventDefault();

        const body = this.inputTarget.value.trim();
        if (body === '') return this.showError('Write something before posting.');
        if (body.length > this.maxLengthValue) {
            return this.showError(`Comments are limited to ${this.maxLengthValue} characters.`);
        }

        const comments = await this.request(this.urlValue, 'POST', { body });
        if (comments === null) return;

        this.inputTarget.value = '';
        this.render(comments);
    }

    composerKeydown(event) {
        if (event.key === 'Enter' && (event.ctrlKey || event.metaKey)) {
            event.preventDefault();
            this.submit(event);
        }
    }

    async saveEdit(comment, body) {
        if (body.trim() === '') return this.showError('A comment cannot be empty.');

        const comments = await this.request(comment.url, 'PATCH', { body: body.trim() });
        if (comments === null) return;

        this.editingId = null;
        this.render(comments);
    }

    async remove(comment) {
        if (!confirm('Delete this comment?')) return;

        const comments = await this.request(comment.url, 'DELETE');
        if (comments === null) return;

        this.render(comments);
    }

    async request(url, method, payload = null) {
        this.hideError();

        try {
            const response = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfTokenValue,
                },
                body: payload === null ? null : JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok) {
                this.showError(data.error ?? 'Something went wrong, try again.');
                return null;
            }

            return data.comments ?? [];
        } catch {
            this.showError('Could not reach the server, try again.');
            return null;
        }
    }

    render(comments) {
        this.threadTarget.innerHTML = '';
        this.countTarget.textContent = comments.length > 0 ? comments.length : '';
        this.emptyTarget.hidden = comments.length > 0;

        for (const comment of comments) {
            this.threadTarget.appendChild(
                comment.id === this.editingId ? this.buildEditor(comment) : this.buildComment(comment),
            );
        }

        this.syncBadge(comments.length);
    }

    buildComment(comment) {
        const item = this.buildItem(comment);
        const main = item.querySelector('.comment-main');

        const body = document.createElement('div');
        body.className = 'comment-body';
        body.textContent = comment.body;
        main.appendChild(body);

        if (comment.canModify && !this.readOnlyValue) {
            const actions = document.createElement('div');
            actions.className = 'comment-actions';
            actions.appendChild(this.buildAction('Edit', () => {
                this.editingId = comment.id;
                this.replaceItem(item, this.buildEditor(comment));
            }));
            actions.appendChild(this.buildAction('Delete', () => this.remove(comment)));
            main.appendChild(actions);
        }

        return item;
    }

    buildEditor(comment) {
        const item = this.buildItem(comment);
        const main = item.querySelector('.comment-main');

        const input = document.createElement('textarea');
        input.className = 'comment-edit-input';
        input.rows = 3;
        input.maxLength = this.maxLengthValue;
        input.value = comment.body;
        main.appendChild(input);

        const actions = document.createElement('div');
        actions.className = 'comment-actions';
        actions.appendChild(this.buildAction('Save', () => this.saveEdit(comment, input.value), 'btn-primary'));
        actions.appendChild(this.buildAction('Cancel', () => {
            this.editingId = null;
            this.replaceItem(item, this.buildComment(comment));
        }));
        main.appendChild(actions);

        return item;
    }

    buildItem(comment) {
        const item = document.createElement('li');
        item.className = comment.mine ? 'comment mine' : 'comment';

        const avatar = document.createElement('span');
        avatar.className = 'comment-avatar';
        avatar.textContent = this.initials(comment.author);
        avatar.title = comment.author;
        item.appendChild(avatar);

        const main = document.createElement('div');
        main.className = 'comment-main';

        const meta = document.createElement('div');
        meta.className = 'comment-meta';

        const author = document.createElement('span');
        author.className = 'comment-author';
        author.textContent = comment.author;
        meta.appendChild(author);

        const time = document.createElement('span');
        time.className = 'comment-time';
        time.textContent = this.timeAgo(comment.createdAt);
        time.title = new Date(comment.createdAt).toLocaleString();
        meta.appendChild(time);

        if (comment.editedAt) {
            const edited = document.createElement('span');
            edited.className = 'comment-edited';
            edited.textContent = 'edited';
            edited.title = new Date(comment.editedAt).toLocaleString();
            meta.appendChild(edited);
        }

        main.appendChild(meta);
        item.appendChild(main);

        return item;
    }

    buildAction(label, onClick, extraClass = 'btn-link') {
        const button = document.createElement('button');
        button.type = 'button';
        button.className = `btn ${extraClass} btn-small`;
        button.textContent = label;
        button.addEventListener('click', (event) => {
            event.stopPropagation();
            onClick();
        });

        return button;
    }

    replaceItem(item, replacement) {
        item.replaceWith(replacement);
    }

    syncBadge(count) {
        const badge = document.querySelector(`[data-card-comment-badge="${this.cardIdValue}"]`);
        if (badge === null) return;

        badge.hidden = count === 0;

        const counter = badge.querySelector('[data-comment-count-text]');
        if (counter !== null) counter.textContent = count > 0 ? count : '';
    }

    initials(name) {
        return name
            .split(/\s+/)
            .filter((part) => part !== '')
            .slice(0, 2)
            .map((part) => part[0].toUpperCase())
            .join('') || '?';
    }

    timeAgo(iso) {
        const seconds = Math.round((Date.now() - new Date(iso).getTime()) / 1000);
        if (seconds < 60) return 'just now';

        for (const [limit, size, unit] of [
            [3600, 60, 'minute'],
            [86400, 3600, 'hour'],
            [604800, 86400, 'day'],
        ]) {
            if (seconds < limit) {
                const value = Math.floor(seconds / size);
                return `${value} ${unit}${value === 1 ? '' : 's'} ago`;
            }
        }

        return new Date(iso).toLocaleDateString();
    }

    showError(message) {
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
    }

    hideError() {
        this.errorTarget.hidden = true;
    }
}
