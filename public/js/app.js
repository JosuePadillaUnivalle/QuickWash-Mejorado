const dialog = document.getElementById('confirm-dialog');
let pendingForm = null;
document.addEventListener('submit', event => {
        const form = event.target.closest('form[data-confirm]');
        if (!form) return;
        if (form.dataset.confirmed === 'yes') {
            form.querySelectorAll('button[type="submit"], button:not([type])').forEach(button => button.disabled = true);
            return;
        }
        event.preventDefault();
        pendingForm = form;
        let message = form.dataset.confirm;
        if (form.id === 'booking-form') {
            const machine = form.querySelector('input[name="machine_id"]:checked');
            const count = form.querySelector('[name="garment_count"]').value;
            message = '¿Reservar ' + machine.dataset.name + ' para ' + count + ' prendas el ' + form.querySelector('[name="date"]').value + ' a las ' + form.querySelector('[name="time"]').value + '?';
        } else if (form.querySelector('select[name="status"]')) {
            const select = form.querySelector('select[name="status"]');
            message += ' Nuevo estado: ' + select.options[select.selectedIndex].text + '.';
        }
        document.getElementById('confirm-text').textContent = message;
        dialog.showModal();
});
dialog?.addEventListener('close', () => {
    if (dialog.returnValue === 'confirm' && pendingForm) {
        pendingForm.dataset.confirmed = 'yes';
        pendingForm.requestSubmit();
    }
    pendingForm = null;
    dialog.returnValue = '';
});
document.querySelectorAll('input[name="machine_id"]').forEach(input => {
    input.addEventListener('change', () => {
        document.getElementById('selected-machine').textContent = input.dataset.name;
    });
});

// Refresh only results so filters and confirmation dialogs keep their state.
if (document.getElementById('live-reservations')) {
    let refreshing = false;
    setInterval(async () => {
        if (refreshing || document.hidden || dialog?.open || document.activeElement?.closest('#live-reservations')) return;
        refreshing = true;
        try {
            const response = await fetch(location.href, {headers: {'X-Requested-With': 'XMLHttpRequest'}, cache: 'no-store'});
            const html = new DOMParser().parseFromString(await response.text(), 'text/html');
            const results = html.getElementById('live-reservations');
            if (!response.ok || !results) throw new Error('No disponible');
            if (!dialog?.open && !document.activeElement?.closest('#live-reservations')) {
                document.getElementById('live-reservations').replaceChildren(...results.childNodes);
            }
            document.getElementById('live-status').textContent = 'Estados actualizados · Consulta automática cada 10 segundos';
        } catch {
            document.getElementById('live-status').textContent = 'No se pudo actualizar. Comprueba tu conexión o pulsa Actualizar.';
        } finally { refreshing = false; }
    }, 10000);
}
