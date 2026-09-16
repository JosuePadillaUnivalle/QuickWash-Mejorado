const dialog = document.getElementById('confirm-dialog');
let pendingForm = null;
document.querySelectorAll('form[data-confirm]').forEach(form => {
    form.addEventListener('submit', event => {
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
