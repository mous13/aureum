import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'title', 'roomSection', 'featureSection', 'storageSection', 'stepsSection', 'commentsSection',
        'type', 'bathroom', 'viewValue', 'bed', 'featureType', 'steps',
        'department', 'contents', 'comments',
    ];

    connect() {
        this.modal = this.element.querySelector('.modal');
    }

    open(event) {
        const cell = event.currentTarget;
        const isRoom = cell.dataset.kind === 'room';
        const isStorage = !isRoom && cell.dataset.featureType === 'Storage';
        const comments = cell.dataset.comments ?? '';

        this.titleTarget.textContent = cell.dataset.title;

        this.roomSectionTarget.hidden = !isRoom;
        this.featureSectionTarget.hidden = isRoom;
        this.storageSectionTarget.hidden = !isStorage;
        this.stepsSectionTarget.hidden = isStorage;
        this.commentsSectionTarget.hidden = comments === '';

        if (isRoom) {
            this.typeTarget.textContent = cell.dataset.roomType;
            this.bathroomTarget.textContent = cell.dataset.roomBathroom;
            this.viewValueTarget.textContent = cell.dataset.roomView;
            this.bedTarget.textContent = cell.dataset.roomBed;
        } else {
            this.featureTypeTarget.textContent = cell.dataset.featureType;
        }

        if (isStorage) {
            this.departmentTarget.textContent = cell.dataset.department || '—';
            this.contentsTarget.textContent = cell.dataset.contents || '—';
        } else {
            this.stepsTarget.textContent = cell.dataset.steps;
        }

        this.commentsTarget.textContent = comments;

        this.modal?.classList.add('open');
    }

    close() {
        this.modal?.classList.remove('open');
    }
}
