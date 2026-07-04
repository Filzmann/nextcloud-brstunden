(function() {
    function esc(value) {
        return String(value ?? '').replace(/[&<>"']/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        }[char]));
    }

    function hours(value) {
        const number = Number(value || 0);

        return number.toLocaleString('de-DE', {
            minimumFractionDigits: number % 1 === 0 ? 0 : 1,
            maximumFractionDigits: 2
        });
    }

    window.BRStunden = window.BRStunden || {};
    window.BRStunden.format = { esc, hours };
})();
