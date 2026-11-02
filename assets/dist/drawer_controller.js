import { Controller } from '@hotwired/stimulus';

const FOCUSABLE = [
    'a[href]',
    'button:not([disabled])',
    'input:not([disabled]):not([type="hidden"])',
    'select:not([disabled])',
    'textarea:not([disabled])',
    '[tabindex]:not([tabindex="-1"])',
].join(',');

const FIELDS = 'input:not([disabled]):not([type="hidden"]):not([type="radio"]):not([type="checkbox"]), select:not([disabled]), textarea:not([disabled])';

let openCount = 0;

export default class extends Controller {
    static targets = ['drawer', 'panel'];
    static values = { open: Boolean, autofocus: Boolean };

    connect() {
        this.isOpen = false;
        this.onKeydown = this.onKeydown.bind(this);
        if (this.openValue) {
            this.open();
        }
    }

    disconnect() {
        if (this.isOpen) {
            this.release();
        }
    }

    open(event) {
        if (this.isOpen) {
            return;
        }
        this.isOpen = true;
        this.returnFocus = event?.currentTarget ?? document.activeElement;
        this.drawerTarget.hidden = false;
        document.addEventListener('keydown', this.onKeydown);
        openCount += 1;
        document.documentElement.classList.add('drawer-locked');
        requestAnimationFrame(() => this.focusInitial());
    }

    close() {
        if (!this.isOpen) {
            return;
        }
        this.release();
        this.drawerTarget.hidden = true;
        if (this.returnFocus && document.contains(this.returnFocus)) {
            this.returnFocus.focus();
        }
    }

    release() {
        this.isOpen = false;
        document.removeEventListener('keydown', this.onKeydown);
        openCount = Math.max(0, openCount - 1);
        if (openCount === 0) {
            document.documentElement.classList.remove('drawer-locked');
        }
    }

    focusInitial() {
        const invalid = this.panelTarget.querySelector('[aria-invalid="true"], .form-row:has(p) :is(input, select, textarea)');
        const fine = window.matchMedia('(pointer: fine)').matches;
        const target = invalid ?? (fine && this.autofocusValue ? this.panelTarget.querySelector(FIELDS) : null);
        (target ?? this.panelTarget).focus({ preventScroll: target === null });
    }

    onKeydown(event) {
        if (event.key === 'Escape') {
            if (document.querySelector('.modal.open')) {
                return;
            }
            event.preventDefault();
            this.close();
            return;
        }
        if (event.key === 'Tab') {
            this.trapFocus(event);
        }
    }

    trapFocus(event) {
        const items = [...this.panelTarget.querySelectorAll(FOCUSABLE)]
            .filter((el) => el.offsetParent !== null || el === document.activeElement);
        if (items.length === 0) {
            event.preventDefault();
            return;
        }
        const first = items[0];
        const last = items[items.length - 1];
        const active = document.activeElement;
        if (event.shiftKey && (active === first || active === this.panelTarget)) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && active === last) {
            event.preventDefault();
            first.focus();
        } else if (!this.panelTarget.contains(active)) {
            event.preventDefault();
            first.focus();
        }
    }
}
