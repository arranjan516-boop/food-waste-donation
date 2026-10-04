/* Notification bell: auto-refresh unread count every 30s */
(function () {
    const url = (typeof BASE_URL !== 'undefined' ? BASE_URL : '/food_donation/') + 'api/notifications.php?action=count';
    async function refresh() {
        try {
            const r = await fetch(url, { credentials: 'same-origin' });
            if (!r.ok) return;
            const data = await r.json();
            const bell = document.querySelector('.bell');
            if (!bell) return;
            let badge = bell.querySelector('.count');
            if (data.count > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'count';
                    bell.appendChild(badge);
                }
                badge.textContent = data.count;
            } else if (badge) {
                badge.remove();
            }
        } catch (e) { /* silent */ }
    }
    setInterval(refresh, 30000);
})();
