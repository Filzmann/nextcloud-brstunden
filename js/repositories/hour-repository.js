(function() {
    const { Repository } = window.LocalBase.repositories;

    class HourRepository extends Repository {
        state() {
            return this.request('/api/state');
        }

        yearOverview(year) {
            return this.request('/api/years/' + this.encode(year));
        }

        saveEntry(payload) {
            return this.post('/api/entries', payload);
        }

        deleteEntry(year, month) {
            return this.request('/api/entries/' + this.encode(year) + '/' + this.encode(month), {
                method: 'DELETE'
            });
        }

        reminderPreview() {
            return this.request('/api/reminders/preview');
        }

        payrollPdfUrl(year, month) {
            return OC.generateUrl('/apps/brstunden/api/entries/' + this.encode(year) + '/' + this.encode(month) + '/payroll.pdf');
        }

    }

    window.BRStunden = window.BRStunden || {};
    window.BRStunden.repositories = window.BRStunden.repositories || {};
    window.BRStunden.repositories.HourRepository = HourRepository;
})();
