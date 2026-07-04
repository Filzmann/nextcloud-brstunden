(function() {
    class HourRepository {
        constructor(api) {
            this.api = api;
        }

        state() {
            return this.api.request('/api/state');
        }

        yearOverview(year) {
            return this.api.request('/api/years/' + this.encode(year));
        }

        saveEntry(payload) {
            return this.api.request('/api/entries', {
                method: 'POST',
                body: JSON.stringify(payload)
            });
        }

        deleteEntry(year, month) {
            return this.api.request('/api/entries/' + this.encode(year) + '/' + this.encode(month), {
                method: 'DELETE'
            });
        }

        reminderPreview() {
            return this.api.request('/api/reminders/preview');
        }

        payrollPdfUrl(year, month) {
            return OC.generateUrl('/apps/brstunden/api/entries/' + this.encode(year) + '/' + this.encode(month) + '/payroll.pdf');
        }

        encode(value) {
            return encodeURIComponent(String(value));
        }
    }

    window.BRStunden = window.BRStunden || {};
    window.BRStunden.repositories = window.BRStunden.repositories || {};
    window.BRStunden.repositories.HourRepository = HourRepository;
})();
