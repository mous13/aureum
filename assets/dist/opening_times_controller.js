import { Controller } from '@hotwired/stimulus';

const DAYS = [
    ['mon', 'Monday'],
    ['tue', 'Tuesday'],
    ['wed', 'Wednesday'],
    ['thu', 'Thursday'],
    ['fri', 'Friday'],
    ['sat', 'Saturday'],
    ['sun', 'Sunday'],
];

export default class extends Controller {
    static targets = ['grid', 'input', 'candidates', 'googleStatus', 'error'];
    static values = {
        initial: String,
        googleEnabled: Boolean,
        placeId: String,
        searchUrl: String,
        linkUrl: String,
        unlinkUrl: String,
        csrfToken: String,
    };

    connect()
    {
        this.times = this.parseInitial();
        this.renderGrid();
        if (this.hasInitial) this.serialize();
        this.renderGoogleStatus();
    }

    parseInitial() {
        try {
            const parsed = JSON.parse(this.initialValue || 'null');
            if (parsed && typeof parsed === 'object') {
                this.hasInitial = true;
                return parsed;
            }
        } catch (e) {
        }
        this.hasInitial = false;
        const empty = {};
        DAYS.forEach(([key]) => empty[key] = { closed: true, ranges: [] });
        return empty;
    }

    renderGrid() {
        this.gridTarget.innerHTML = '';
        DAYS.forEach(([key, label]) => {
            const day = this.times[key] || { closed: true, ranges: [] };
            const row = document.createElement('div');
            row.className = 'flex gap-2 items-center mb-2';
            row.dataset.day = key;

            const name = document.createElement('span');
            name.textContent = label;
            name.style.width = '6rem';
            row.appendChild(name);

            const closedLabel = document.createElement('label');
            closedLabel.className = 'flex gap-1 items-center text-small';
            const closed = document.createElement('input');
            closed.type = 'checkbox';
            closed.checked = day.closed;
            closed.addEventListener('change', () => this.toggleClosed(key, closed.checked));
            closedLabel.appendChild(closed);
            closedLabel.appendChild(document.createTextNode('Closed'));
            row.appendChild(closedLabel);

            const ranges = document.createElement('div');
            ranges.className = 'flex gap-2 items-center flex-wrap';
            if (!day.closed) {
                day.ranges.forEach((range, index) => {
                    ranges.appendChild(this.buildRange(key, index, range));
                });
                const add = document.createElement('button');
                add.type = 'button';
                add.className = 'btn-link btn-small';
                add.textContent = '+ hours';
                add.addEventListener('click', () => this.addRange(key));
                ranges.appendChild(add);
            }
            row.appendChild(ranges);

            this.gridTarget.appendChild(row);
        });
    }

    buildRange(dayKey, index, range) {
        const wrap = document.createElement('span');
        wrap.className = 'flex gap-1 items-center';

        const from = document.createElement('input');
        from.type = 'time';
        from.value = range[0];
        from.addEventListener('change', () => this.updateRange(dayKey, index, 0, from.value));
        wrap.appendChild(from);

        wrap.appendChild(document.createTextNode('–'));

        const to = document.createElement('input');
        to.type = 'time';
        to.value = range[1];
        to.addEventListener('change', () => this.updateRange(dayKey, index, 1, to.value));
        wrap.appendChild(to);

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'btn-link btn-icon btn-small';
        remove.innerHTML = '<i class="ph ph-x"></i>';
        remove.addEventListener('click', () => this.removeRange(dayKey, index));
        wrap.appendChild(remove);

        return wrap;
    }

    toggleClosed(dayKey, isClosed) {
        this.times[dayKey].closed = isClosed;
        if (isClosed) this.times[dayKey].ranges = [];
        this.renderGrid();
        this.serialize();
    }

    addRange(dayKey) {
        this.times[dayKey].ranges.push(['09:00', '17:00']);
        this.renderGrid();
        this.serialize();
    }

    updateRange(dayKey, index, position, value) {
        if (value) {
            this.times[dayKey].ranges[index][position] = value;
        }
        this.serialize();
    }

    removeRange(dayKey, index) {
        this.times[dayKey].ranges.splice(index, 1);
        this.renderGrid();
        this.serialize();
    }

    serialize() {
        this.inputTarget.value = JSON.stringify(this.times);
    }

    async findOnGoogle() {
        this.clearError();
        const results = await this.request(this.searchUrlValue);
        if (results === null) return;
        this.renderCandidates(results);
    }

    renderCandidates(results) {
        this.candidatesTarget.innerHTML = '';
        if (results.length === 0) {
            this.candidatesTarget.textContent = 'No matches found.';
            return;
        }
        results.forEach((place) => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn-link btn-small flex gap-2';
            button.textContent = `${place.name} — ${place.address}`;
            button.addEventListener('click', () => this.link(place.id));
            this.candidatesTarget.appendChild(button);
        });
    }

    async link(placeId) {
        this.clearError();
        const data = await this.request(this.linkUrlValue, { placeId });
        if (data === null) return;
        this.placeIdValue = placeId;
        if (data.openingTimes) {
            this.times = data.openingTimes;
            this.renderGrid();
            this.serialize();
        }
        this.candidatesTarget.innerHTML = '';
        this.renderGoogleStatus();
    }

    async unlink() {
        this.clearError();
        const data = await this.request(this.unlinkUrlValue);
        if (data === null) return;
        this.placeIdValue = '';
        this.renderGoogleStatus();
    }

    renderGoogleStatus() {
        if (!this.hasGoogleStatusTarget) return;
        if (!this.googleEnabledValue) {
            this.googleStatusTarget.innerHTML = '';
            return;
        }
        this.googleStatusTarget.innerHTML = '';
        if (this.placeIdValue) {
            const status = document.createElement('span');
            status.className = 'text-small';
            status.textContent = 'Linked to Google. ';
            this.googleStatusTarget.appendChild(status);
            const unlink = document.createElement('button');
            unlink.type = 'button';
            unlink.className = 'btn-link btn-small';
            unlink.textContent = 'Unlink';
            unlink.addEventListener('click', () => this.unlink());
            this.googleStatusTarget.appendChild(unlink);
        } else if (this.searchUrlValue) {
            const find = document.createElement('button');
            find.type = 'button';
            find.className = 'btn btn-primary luxury-light square btn-small';
            find.textContent = 'Find on Google';
            find.addEventListener('click', () => this.findOnGoogle());
            this.googleStatusTarget.appendChild(find);
        }
    }

    async request(url, body = {}) {
        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ ...body, _token: this.csrfTokenValue }),
            });
            if (!response.ok) {
                this.showError('Google request failed. Try again later.');
                return null;
            }
            return await response.json();
        } catch (e) {
            this.showError('Google request failed. Try again later.');
            return null;
        }
    }

    showError(message) {
        if (this.hasErrorTarget) this.errorTarget.textContent = message;
    }

    clearError() {
        if (this.hasErrorTarget) this.errorTarget.textContent = '';
    }
}
