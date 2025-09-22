(function () {
    const markReadButtons = document.querySelectorAll('[data-notification-mark-read]');
    markReadButtons.forEach(btn => {
        btn.addEventListener('click', async function () {
            const id = this.getAttribute('data-notification-id');
            try {
                await fetch(`${this.dataset.endpoint}?action=mark-read`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: id })
                });
                this.closest('.notification')?.classList.add('notification--read');
            } catch (err) {
                console.error('Failed to mark notification as read', err);
            }
        });
    });
})();
