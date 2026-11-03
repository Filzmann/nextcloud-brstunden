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

    const documentForm = document.getElementById('brstunden-document-settings');
    const documentStatus = document.getElementById('brstunden-document-status');
    const previewButton = document.getElementById('brstunden-document-preview');
    const approveButton = document.getElementById('brstunden-document-approve');
    if (!documentForm) return;

    const documentValues = () => Object.fromEntries(
        ['organizationName', 'organizationAddress', 'issuePlace', 'introText', 'footerText']
            .map((name) => [name, String(documentForm.elements[name]?.value || '')]),
    );
    const resolvePreview = (text, values) => {
        const examples = {
            organization_name: values.organizationName,
            organization_address: values.organizationAddress.replace(/\n/g, ', '),
            issue_place: values.issuePlace,
            issue_date: '03.07.2026',
            month_label: 'Juli 2026',
            member_name: 'Beispielmitglied',
            member_address: 'Musterweg 2, 12345 Beispielstadt',
            template_version: 'Entwurf',
            output_locale: 'de-DE',
        };
        return text.replace(/\{\{([a-z_]+)\}\}/g, (placeholder, name) => examples[name] ?? placeholder);
    };
    const updatePreview = () => {
        const values = documentValues();
        document.getElementById('brstunden-preview-organization').textContent = values.organizationName;
        document.getElementById('brstunden-preview-address').textContent = values.organizationAddress;
        document.getElementById('brstunden-preview-intro').textContent = resolvePreview(values.introText, values);
        document.getElementById('brstunden-preview-issue').textContent = `${values.issuePlace}, 03.07.2026`;
        document.getElementById('brstunden-preview-footer').textContent = resolvePreview(values.footerText, values);
    };
    const request = async (url, method, body = undefined) => {
        const response = await fetch(OC.generateUrl(url), {
            method,
            headers: {'Content-Type': 'application/json', requesttoken: OC.requestToken},
            body: body === undefined ? undefined : JSON.stringify(body),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Die Dokumentkonfiguration konnte nicht verarbeitet werden.');
        return result;
    };

    previewButton?.addEventListener('click', updatePreview);
    documentForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (documentStatus) documentStatus.textContent = 'Entwurf wird gespeichert …';
        try {
            await request('/apps/brstunden/api/document-settings', 'PUT', {document: documentValues()});
            if (documentStatus) documentStatus.textContent = 'Der validierte Entwurf wurde gespeichert und ist noch nicht freigegeben.';
            if (approveButton) approveButton.disabled = false;
            updatePreview();
        } catch (error) {
            if (documentStatus) documentStatus.textContent = error instanceof Error ? error.message : 'Der Entwurf konnte nicht gespeichert werden.';
        }
    });
    approveButton?.addEventListener('click', async () => {
        if (documentStatus) documentStatus.textContent = 'Entwurf wird freigegeben …';
        try {
            const result = await request('/apps/brstunden/api/document-settings/approve', 'POST');
            const version = result.state?.approved?.version;
            if (documentStatus) documentStatus.textContent = `Vorlage v${version || ''} wurde freigegeben.`;
            approveButton.disabled = true;
        } catch (error) {
            if (documentStatus) documentStatus.textContent = error instanceof Error ? error.message : 'Der Entwurf konnte nicht freigegeben werden.';
        }
    });
    updatePreview();
});
