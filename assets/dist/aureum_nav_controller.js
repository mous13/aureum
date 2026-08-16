import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['toggle', 'children', 'caret'];

    toggle() {
        const expanded = !this.childrenTarget.classList.toggle('d-none');

        if (this.hasToggleTarget) {
            this.toggleTarget.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }

        if (this.hasCaretTarget) {
            this.caretTarget.classList.toggle('ph-caret-down', expanded);
            this.caretTarget.classList.toggle('ph-caret-right', !expanded);
        }
    }
}
