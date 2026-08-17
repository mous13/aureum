import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['type', 'typeOption', 'detail', 'vendor', 'serviceHeading', 'serviceIcon'];

    connect() {
        this.update();
    }

    typeTargetConnected() {
        this.update();
    }

    typeOptionTargetConnected() {
        this.update();
    }

    detailTargetConnected() {
        this.update();
    }

    update() {
        const type = this.currentType();
        if (type === null) {
            return;
        }

        this.detailTargets.forEach((field) => {
            const types = (field.dataset.bookingTypes || '').split(' ').filter(Boolean);
            field.hidden = type === '' || !types.includes(type);
        });

        if (this.hasVendorTarget) {
            const label = this.vendorLabels()[type];
            if (label) {
                this.vendorTarget.placeholder = label;
            }
        }

        const meta = this.typeMeta()[type];
        if (meta) {
            if (this.hasServiceHeadingTarget) {
                this.serviceHeadingTarget.textContent = meta.label + ' Details';
            }
            if (this.hasServiceIconTarget) {
                this.serviceIconTarget.className = 'icon ' + meta.icon;
            }
        }
    }

    currentType() {
        if (this.hasTypeTarget) {
            return this.typeTarget.value;
        }
        if (this.hasTypeOptionTarget) {
            const checked = this.typeOptionTargets.find((input) => input.checked);
            return checked ? checked.value : '';
        }
        return null;
    }

    vendorLabels() {
        if (!this.hasVendorTarget) {
            return {};
        }
        try {
            return JSON.parse(this.vendorTarget.dataset.vendorLabels || '{}');
        } catch {
            return {};
        }
    }

    typeMeta() {
        try {
            return JSON.parse(this.element.dataset.typeMeta || '{}');
        } catch {
            return {};
        }
    }
}
