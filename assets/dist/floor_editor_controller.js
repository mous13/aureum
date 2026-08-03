import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'grid', 'panel', 'panelTitle', 'error', 'deleteBtn', 'modeBtn',
        'roomFields', 'number', 'roomType', 'bathroomStyle', 'viewSelect', 'bedSize', 'hasStairs', 'roomComments',
        'featureFields', 'featureLabel', 'hasSteps', 'stepsWrapper',
        'storageFields', 'department', 'contents', 'commentsWrapper', 'featureComments',
    ];

    static values = {
        width: Number,
        height: Number,
        rooms: Array,
        features: Array,
        roomSaveUrl: String,
        roomDeleteUrl: String,
        featureSaveUrl: String,
        featureDeleteUrl: String,
    };

    connect() {
        this.rooms = this.roomsValue;
        this.features = this.featuresValue;
        this.mode = 'room';
        this.selection = new Set();
        this.editing = null;
        this.painting = false;

        this.renderGrid();

        this.onPointerUp = () => { this.painting = false; };
        document.addEventListener('pointerup', this.onPointerUp);
        document.addEventListener('pointercancel', this.onPointerUp);

        this.onPointerMove = (event) => this.paintFromPoint(event);
        this.gridTarget.addEventListener('pointermove', this.onPointerMove);
    }

    disconnect() {
        document.removeEventListener('pointerup', this.onPointerUp);
        document.removeEventListener('pointercancel', this.onPointerUp);
        this.gridTarget.removeEventListener('pointermove', this.onPointerMove);
    }

    paintFromPoint(event) {
        if (!this.painting) return;
        const el = document.elementFromPoint(event.clientX, event.clientY);
        const key = el?.dataset?.key;
        if (key) this.addCell(key);
    }

    setMode(event) {
        this.mode = event.currentTarget.dataset.mode;
        this.modeBtnTargets.forEach((b) => b.classList.toggle('active', b === event.currentTarget));
        if (this.editing === null && this.selection.size > 0) {
            this.syncPanelToMode();
        }
    }


    renderGrid() {
        const occupied = new Map();
        for (const room of this.rooms) {
            for (const [x, y] of room.cells) occupied.set(`${x}:${y}`, { kind: 'room', shape: room });
        }
        for (const feature of this.features) {
            for (const [x, y] of feature.cells) occupied.set(`${x}:${y}`, { kind: 'feature', shape: feature });
        }

        this.gridTarget.innerHTML = '';
        for (let y = 0; y < this.heightValue; y++) {
            for (let x = 0; x < this.widthValue; x++) {
                const key = `${x}:${y}`;
                const cell = document.createElement('div');
                cell.style.gridColumn = x + 1;
                cell.style.gridRow = y + 1;

                const hit = occupied.get(key);
                if (hit) {
                    const { kind, shape } = hit;
                    const base = kind === 'room'
                        ? 'room-cell match'
                        : `feature-cell feature-${shape.type.toLowerCase()}`;
                    const isHallway = kind === 'feature' && shape.type === 'Hallway';
                    cell.className = base + this.edgeClasses(shape.cells, x, y, isHallway ? occupied : null);
                    if (this.isLabelCell(shape.cells, x, y)) {
                        const text = kind === 'room' ? shape.number : (shape.label || shape.type);
                        const steps = kind === 'feature' && shape.hasSteps ? ' \u25B2' : '';
                        cell.innerHTML = `<span class="room-label">${text}${steps}</span>`;
                    }
                    cell.addEventListener('pointerdown', () => this.editShape(hit));
                } else {
                    cell.className = 'grid-cell';
                    cell.dataset.key = key;
                    if (this.selection.has(key)) cell.classList.add('selected');
                    cell.addEventListener('pointerdown', (e) => {
                        e.preventDefault();
                        e.target.releasePointerCapture?.(e.pointerId);
                        this.painting = true;
                        this.toggleCell(key);
                    });
                }

                this.gridTarget.appendChild(cell);
            }
        }
    }
    edgeClasses(cells, x, y, occupied = null) {
        const has = (cx, cy) => cells.some(([rx, ry]) => rx === cx && ry === cy);
        let cls = '';
        for (const [side, cx, cy] of [['t', x, y - 1], ['b', x, y + 1], ['l', x - 1, y], ['r', x + 1, y]]) {
            if (has(cx, cy)) continue;
            cls += ` edge-${side}`;
            if (occupied !== null && !occupied.has(`${cx}:${cy}`)) cls += ` wall-${side}`;
        }
        return cls;
    }

    isLabelCell(cells, x, y) {
        let best = null;
        for (const [cx, cy] of cells) {
            if (best === null || cy < best[1] || (cy === best[1] && cx < best[0])) best = [cx, cy];
        }
        return best !== null && best[0] === x && best[1] === y;
    }


    toggleCell(key) {
        this.selection.has(key) ? this.selection.delete(key) : this.selection.add(key);
        this.afterSelectionChange();
    }

    addCell(key) {
        if (!this.selection.has(key)) {
            this.selection.add(key);
            this.afterSelectionChange();
        }
    }

    afterSelectionChange() {
        if (this.selection.size > 0 && this.editing === null) {
            this.syncPanelToMode();
        }
        this.renderGrid();
    }


    syncPanelToMode() {
        const isRoom = this.mode === 'room';
        this.roomFieldsTarget.hidden = !isRoom;
        this.featureFieldsTarget.hidden = isRoom;
        if (!isRoom) {
            this.syncFeatureFields(this.mode);
        }
        this.openPanel(isRoom ? 'New Room' : `New ${this.mode}`);
    }

    syncFeatureFields(type) {
        const isStorage = type === 'Storage';
        this.storageFieldsTarget.hidden = !isStorage;
        this.commentsWrapperTarget.hidden = isStorage;
        this.stepsWrapperTarget.hidden = type !== 'Hallway';
    }

    openPanel(title) {
        this.panelTitleTarget.textContent = title;
        this.errorTarget.hidden = true;
        this.deleteBtnTarget.hidden = this.editing === null;
        this.panelTarget.hidden = false;
    }

    editShape(hit) {
        this.editing = hit;
        const { kind, shape } = hit;
        this.selection = new Set(shape.cells.map(([x, y]) => `${x}:${y}`));

        this.roomFieldsTarget.hidden = kind !== 'room';
        this.featureFieldsTarget.hidden = kind !== 'feature';

        if (kind === 'room') {
            this.numberTarget.value = shape.number;
            this.roomTypeTarget.value = shape.roomTypeId ?? '';
            this.bathroomStyleTarget.value = shape.bathroomStyle;
            this.viewSelectTarget.value = shape.view;
            this.bedSizeTarget.value = shape.bedSize;
            this.hasStairsTarget.checked = shape.hasStairs;
            this.roomCommentsTarget.value = shape.comments ?? '';
            this.rooms = this.rooms.filter((r) => r !== shape);
            this.openPanel(`Edit Room ${shape.number}`);
        } else {
            this.mode = shape.type;
            this.modeBtnTargets.forEach((b) => b.classList.toggle('active', b.dataset.mode === shape.type));
            this.featureLabelTarget.value = shape.label ?? '';
            this.hasStepsTarget.checked = shape.hasSteps;
            this.departmentTarget.value = shape.department ?? '';
            this.contentsTarget.value = shape.contents ?? '';
            this.featureCommentsTarget.value = shape.comments ?? '';
            this.syncFeatureFields(shape.type);
            this.features = this.features.filter((f) => f !== shape);
            this.openPanel(`Edit ${shape.label || shape.type}`);
        }

        this.renderGrid();
    }

    cancel() {
        if (this.editing !== null) {
            this.editing.kind === 'room'
                ? this.rooms.push(this.editing.shape)
                : this.features.push(this.editing.shape);
        }
        this.resetPanel();
    }

    resetPanel() {
        this.editing = null;
        this.selection.clear();
        this.numberTarget.value = '';
        this.featureLabelTarget.value = '';
        this.hasStairsTarget.checked = false;
        this.hasStepsTarget.checked = false;
        this.roomCommentsTarget.value = '';
        this.departmentTarget.value = '';
        this.contentsTarget.value = '';
        this.featureCommentsTarget.value = '';
        this.panelTarget.hidden = true;
        this.renderGrid();
    }


    async saveShape() {
        const isRoom = this.editing !== null ? this.editing.kind === 'room' : this.mode === 'room';
        const cells = [...this.selection].map((key) => key.split(':').map(Number));
        if (cells.length === 0) return this.showError('Select at least one square');

        if (isRoom) {
            const payload = {
                id: this.editing?.shape.id ?? null,
                number: this.numberTarget.value.trim(),
                roomTypeId: this.roomTypeTarget.value ? Number(this.roomTypeTarget.value) : null,
                bathroomStyle: this.bathroomStyleTarget.value,
                view: this.viewSelectTarget.value,
                bedSize: this.bedSizeTarget.value,
                hasStairs: this.hasStairsTarget.checked,
                comments: this.roomCommentsTarget.value.trim() || null,
                cells,
            };
            if (payload.number === '') return this.showError('Room number is required');
            if (payload.roomTypeId === null) return this.showError('Create a room type first');
            await this.post(this.roomSaveUrlValue, payload, (id) => this.rooms.push({ ...payload, id }));
        } else {
            const type = this.editing?.shape.type ?? this.mode;
            const isStorage = type === 'Storage';
            const payload = {
                id: this.editing?.shape.id ?? null,
                type,
                label: this.featureLabelTarget.value.trim() || null,
                hasSteps: type === 'Stairs' ? true : this.hasStepsTarget.checked,
                department: isStorage ? this.departmentTarget.value.trim() || null : null,
                contents: isStorage ? this.contentsTarget.value.trim() || null : null,
                comments: isStorage ? null : this.featureCommentsTarget.value.trim() || null,
                cells,
            };
            await this.post(this.featureSaveUrlValue, payload, (id) => this.features.push({ ...payload, id }));
        }
    }

    async post(url, payload, onSuccess) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const body = await response.json();
        if (!response.ok) return this.showError(body.error ?? 'Something went wrong');

        onSuccess(body.id);
        this.resetPanel();
    }

    async deleteShape() {
        if (this.editing === null) return;
        const { kind, shape } = this.editing;
        const name = kind === 'room' ? `room ${shape.number}` : (shape.label || shape.type).toLowerCase();
        if (!confirm(`Delete ${name}?`)) return;

        const template = kind === 'room' ? this.roomDeleteUrlValue : this.featureDeleteUrlValue;
        const response = await fetch(template.replace('__ID__', shape.id), { method: 'DELETE' });
        if (!response.ok) return this.showError('Could not delete');

        this.resetPanel();
    }

    showError(message) {
        this.errorTarget.textContent = message;
        this.errorTarget.hidden = false;
    }
}