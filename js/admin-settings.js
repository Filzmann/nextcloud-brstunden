document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('brstunden-capability-settings');
    const status = document.getElementById('brstunden-capability-status');
    if (!form) return;

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const assignments = {};
        for (const fieldset of form.querySelectorAll('[data-capability]')) {
            const capability = String(fieldset.dataset.capability || '');
            assignments[capability] = Array.from(fieldset.querySelectorAll('input[type="checkbox"]:checked')).map((input) => String(input.value));
        }
        if (status) status.textContent = 'Berechtigungen werden gespeichert …';
        try {
            const response = await fetch(OC.generateUrl('/apps/brstunden/api/capability-settings'), {
                method: 'PUT',
                headers: {'Content-Type': 'application/json', requesttoken: OC.requestToken},
                body: JSON.stringify({assignments}),
            });
            const result = await response.json();
            if (!response.ok) throw new Error(result.message || 'Die Berechtigungszuordnung konnte nicht gespeichert werden.');
            if (status) status.textContent = 'Die Berechtigungszuordnung wurde gespeichert.';
        } catch (error) {
            if (status) status.textContent = error instanceof Error ? error.message : 'Die Berechtigungszuordnung konnte nicht gespeichert werden.';
        }
    });
});
