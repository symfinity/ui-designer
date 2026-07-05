import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['refInput'];
    static values = {
        applyUrl: String,
        undoUrl: String,
        selectRoleUrl: String,
    };

    connect() {
        this.activeRole = document.getElementById('studio-active-role')?.textContent?.trim() || 'primary';
    }

    async selectRole(event) {
        const role = event.currentTarget.dataset.role;
        if (!role) return;
        this.activeRole = role;
        const label = document.getElementById('studio-active-role');
        if (label) label.textContent = role;
        document.querySelectorAll('.ui-designer-token-row').forEach((el) => {
            el.classList.toggle('is-selected', el.dataset.role === role);
        });
        await fetch(this.selectRoleUrlValue, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams({ role }),
        });
    }

    async applyColour() {
        const ref = this.refInputTarget?.value?.trim();
        if (!ref) return;
        const payload = await this.postJson(this.applyUrlValue, { role: this.activeRole, ref, confirm: '0' });
        if (payload?.preview_css) this.updatePreviewCss(payload.preview_css);
    }

    async undo() {
        const payload = await this.postJson(this.undoUrlValue, {});
        if (payload?.preview_css) this.updatePreviewCss(payload.preview_css);
    }

    updatePreviewCss(css) {
        const el = document.getElementById('theme-live-css');
        if (el) el.textContent = css;
    }

    async postJson(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(data),
        });
        if (!response.ok) return null;
        return response.json();
    }
}
