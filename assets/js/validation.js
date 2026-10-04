/* Form validation helpers */
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-validate]').forEach(form => {
        form.addEventListener('submit', e => {
            let ok = true;
            form.querySelectorAll('[required]').forEach(f => {
                if (!f.value.trim()) {
                    f.style.borderColor = '#D32F2F';
                    ok = false;
                } else {
                    f.style.borderColor = '';
                }
            });
            if (!ok) {
                e.preventDefault();
                showToast('Please fill all required fields.', 'error');
            }
        });
    });

    // Password match check
    const pwd  = document.querySelector('#password');
    const pwd2 = document.querySelector('#confirm_password');
    if (pwd && pwd2) {
        pwd2.addEventListener('input', () => {
            pwd2.style.borderColor = (pwd.value === pwd2.value) ? '' : '#D32F2F';
        });
    }
});
