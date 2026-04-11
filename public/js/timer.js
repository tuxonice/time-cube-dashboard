document.addEventListener('DOMContentLoaded', function () {
    const timerEl = document.getElementById('timer');
    if (!timerEl) return;

    const startedAt = timerEl.dataset.started;
    if (!startedAt) return;

    const startTime = new Date(startedAt + 'Z').getTime();

    function pad(n) {
        return String(n).padStart(2, '0');
    }

    function update() {
        const now = Date.now();
        let diff = Math.floor((now - startTime) / 1000);
        if (diff < 0) diff = 0;

        const hours = Math.floor(diff / 3600);
        const minutes = Math.floor((diff % 3600) / 60);
        const seconds = diff % 60;

        timerEl.textContent = pad(hours) + ':' + pad(minutes) + ':' + pad(seconds);
    }

    update();
    setInterval(update, 1000);
});
