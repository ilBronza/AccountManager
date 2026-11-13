<script>
(() => {
    const init = () => {
        const table = document.getElementById(@json($tableId));
        const form = document.getElementById('accountmanager-route-associations');
        if (!table || !form || form.dataset.initialized) return;
        form.dataset.initialized = 'true';
        const selected = new Set();
        const checkboxes = () => Array.from(table.querySelectorAll('[data-route-ids]'));
        const ids = box => JSON.parse(box.dataset.routeIds).map(String);
        const refresh = () => {
            checkboxes().forEach(box => {
                const values = ids(box);
                const count = values.filter(id => selected.has(id)).length;
                box.checked = values.length > 0 && count === values.length;
                box.indeterminate = count > 0 && count < values.length;
            });
            form.querySelector('[name="route_ids"]').value = JSON.stringify(Array.from(selected).map(Number));
            form.querySelector('[data-route-selection-count]').textContent = selected.size + ' ' + @json(__('accountmanager::route-access.selected'));
            form.querySelectorAll('[data-route-apply]').forEach(button => button.disabled = selected.size === 0);
        };
        table.addEventListener('change', event => {
            const box = event.target.closest('[data-route-ids]');
            if (!box) return;
            ids(box).forEach(id => box.checked ? selected.add(id) : selected.delete(id));
            refresh();
        });
        table.addEventListener('click', event => {
            const link = event.target.closest('a[data-route-preview]');
            if (!link) return;
            event.preventDefault();
            const url = new URL(link.href);
            const routeId = url.searchParams.get('route');
            url.searchParams.delete('route');
            window.openModalWithData(url.toString(), {route: routeId}, 'POST');
            const modal = document.getElementById('datatablepopup');
            modal.querySelector('iframe').title = link.textContent.trim();
            const overlay = document.createElement('div');
            overlay.className = 'uk-position-cover';
            overlay.style.zIndex = '1';
            overlay.setAttribute('aria-hidden', 'true');
            const dialog = modal.querySelector('.uk-modal-dialog');
            dialog.style.position = 'relative';
            dialog.appendChild(overlay);
            modal.querySelector('.uk-modal-close-default').style.zIndex = '2';
            modal.addEventListener('hidden', () => modal.remove(), {once: true});
        });
        if (window.jQuery) window.jQuery(table).on('draw.dt', refresh);
        refresh();
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, {once: true});
    else init();
})();
</script>
