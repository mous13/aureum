import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['idle', 'busy'];

    show() {
        this.idleTarget.hidden = true;
        this.busyTarget.hidden = false;
    }
}
