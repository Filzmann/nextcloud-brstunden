(function() {
    const { Model } = window.BRStunden.models;

    class HourEntry extends Model {
        constructor(data = {}) {
            super();
            this.id = data.id ?? 0;
            this.userId = data.userId || data.user_id || '';
            this.year = Number(data.year ?? data.entry_year ?? 0);
            this.month = Number(data.month ?? data.entry_month ?? 0);
            this.minutes = Number(data.brMinutes ?? data.minutes ?? 0);
            this.hours = Number(data.brHours ?? data.hours ?? (this.minutes / 60));
            this.brMinutes = Number(data.brMinutes ?? data.minutes ?? 0);
            this.brHours = Number(data.brHours ?? data.hours ?? (this.brMinutes / 60));
            this.fobiMinutes = Number(data.fobiMinutes ?? data.fobi_minutes ?? 0);
            this.fobiHours = Number(data.fobiHours ?? (this.fobiMinutes / 60));
            this.totalMinutes = Number(data.totalMinutes ?? (this.brMinutes + this.fobiMinutes));
            this.totalHours = Number(data.totalHours ?? (this.totalMinutes / 60));
            this.note = data.note || '';
            this.updatedByUid = data.updatedByUid || data.updated_by_uid || null;
            this.createdAt = data.createdAt || data.created_at || '';
            this.updatedAt = data.updatedAt || data.updated_at || '';
        }

        toArray() {
            return {
                id: this.id,
                userId: this.userId,
                year: this.year,
                month: this.month,
                minutes: this.minutes,
                hours: this.hours,
                brMinutes: this.brMinutes,
                brHours: this.brHours,
                fobiMinutes: this.fobiMinutes,
                fobiHours: this.fobiHours,
                totalMinutes: this.totalMinutes,
                totalHours: this.totalHours,
                note: this.note,
                updatedByUid: this.updatedByUid,
                createdAt: this.createdAt,
                updatedAt: this.updatedAt
            };
        }
    }

    window.BRStunden = window.BRStunden || {};
    window.BRStunden.models = window.BRStunden.models || {};
    window.BRStunden.models.HourEntry = HourEntry;
})();
