/* ============================
   FoodShare — Global scripts
   ============================ */
document.addEventListener('DOMContentLoaded', () => {

    // Auto-hide toasts after 5s
    document.querySelectorAll('.toast').forEach(el => {
        setTimeout(() => {
            el.style.transition = 'opacity .4s';
            el.style.opacity = '0';
            setTimeout(() => el.remove(), 400);
        }, 5000);
    });

    // Confirm dialogs on [data-confirm]
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm)) e.preventDefault();
        });
    });

    // Close sidebar on mobile when link clicked
    document.querySelectorAll('.sidebar-menu a').forEach(a => {
        a.addEventListener('click', () => {
            document.querySelector('.sidebar')?.classList.remove('open');
        });
    });
});

// --- Image preview helper (used on upload forms) ---
function previewImage(inputEl, imgEl) {
    const file = inputEl.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = e => {
        imgEl.src = e.target.result;
        imgEl.style.display = 'block';
    };
    reader.readAsDataURL(file);
}

// --- Simple toast ---
function showToast(message, type = 'info') {
    const div = document.createElement('div');
    div.className = 'toast toast-' + type;
    div.textContent = message;
    document.body.appendChild(div);
    setTimeout(() => div.remove(), 4000);
}
