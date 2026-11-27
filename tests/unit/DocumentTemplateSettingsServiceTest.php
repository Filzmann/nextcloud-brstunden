<?php

declare(strict_types=1);

namespace OCA\BrStunden\AppInfo { final class Application { public const APP_ID = 'brstunden'; } }
namespace OCP {
    interface IAppConfig {
        public function getValueString(string $appId, string $key, string $default = ''): string;
        public function setValueString(string $appId, string $key, string $value): void;
    }
    interface IConfig {
        public function getSystemValueString(string $key, string $default = ''): string;
    }
}

namespace {
    use OCA\BrStunden\Service\DocumentTemplateSettingsService;
    use OCP\IAppConfig;
    use function OCA\BrStunden\Tests\assertSameValue;

    $config = new class implements IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };

    $settings = new DocumentTemplateSettingsService($config, new class implements \OCP\IConfig {
        public function getSystemValueString(string $key, string $default = ''): string { return $key === 'locale' ? 'de-DE' : $default; }
    });
    $initial = $settings->state();
    assertSameValue(1, $initial['approved']['version'], 'The existing payroll layout must be represented by approved version 1.');
    assertSameValue('de-DE', $initial['approved']['outputLocale'], 'The current German document output must stay reproducible.');
    assertSameValue('ambulante dienste e.V.', $initial['approved']['organizationName'], 'The existing organization name must remain the default.');
    assertSameValue('Berlin', $initial['approved']['issuePlace'], 'The existing issue place must remain the default.');
    assertSameValue(null, $initial['draft'], 'A fresh installation must not invent an unapproved draft.');

    $draftInput = [
        'organizationName' => 'Beispiel Organisation e.V.',
        'organizationAddress' => "Musterweg 4\n12345 Beispielstadt",
        'issuePlace' => 'Beispielstadt',
        'introText' => 'Abrechnung für {{member_name}} im Monat {{month_label}}',
        'footerText' => 'Ausgestellt am {{issue_date}} in {{issue_place}}',
    ];
    $draftState = $settings->saveDraft($draftInput);
    assertSameValue(2, $draftState['draft']['version'], 'A draft must reserve the next immutable template version.');
    assertSameValue(1, $draftState['approved']['version'], 'Saving a draft must not affect payroll output.');

    $beforeInvalid = $config->values;
    $invalidRejected = false;
    try {
        $settings->saveDraft(array_replace($draftInput, ['introText' => 'Unbekannt: {{php_code}}']));
    } catch (DomainException) {
        $invalidRejected = true;
    }
    assertSameValue(true, $invalidRejected, 'Unknown or executable-looking placeholders must be rejected.');
    assertSameValue($beforeInvalid, $config->values, 'Rejected drafts must not modify the persisted configuration.');

    foreach ([
        array_replace($draftInput, ['organizationAddress' => '']),
        array_replace($draftInput, ['organizationAddress' => "Zeile 1\nZeile 2\nZeile 3\nZeile 4\nZeile 5"]),
        array_replace($draftInput, ['footerText' => "Nicht erlaubt\x00"]),
        array_replace($draftInput, ['introText' => 'Kaputt {{member_name}']),
    ] as $invalidDocument) {
        $rejected = false;
        try {
            $settings->saveDraft($invalidDocument);
        } catch (DomainException) {
            $rejected = true;
        }
        assertSameValue(true, $rejected, 'Empty, overflowing, controlled or malformed document text must be rejected.');
        assertSameValue($beforeInvalid, $config->values, 'Rejected document text must not modify the persisted configuration.');
    }

    $approvedState = $settings->approveDraft();
    assertSameValue(2, $approvedState['approved']['version'], 'Approving a draft must atomically activate its immutable version.');
    assertSameValue(null, $approvedState['draft'], 'An approved draft must no longer be pending.');
    assertSameValue('ambulante dienste e.V.', $settings->version(1)['organizationName'], 'Historical approved versions must remain reproducible.');
    assertSameValue('Beispiel Organisation e.V.', $settings->version(2)['organizationName'], 'The newly approved version must retain its validated content.');

    $resolved = $settings->resolveText(
        $settings->version(2)['footerText'],
        ['issue_date' => '03.07.2026', 'issue_place' => 'Beispielstadt'],
    );
    assertSameValue('Ausgestellt am 03.07.2026 in Beispielstadt', $resolved, 'Allowed placeholders must be replaced as inert text.');

    $stored = json_decode($config->values['brstunden'][DocumentTemplateSettingsService::CONFIG_KEY], true, 16, JSON_THROW_ON_ERROR);
    $stored['approvedVersion'] = 999;
    $config->values['brstunden'][DocumentTemplateSettingsService::CONFIG_KEY] = json_encode($stored, JSON_THROW_ON_ERROR);
    assertSameValue(2, $settings->approved()['version'], 'A broken approved pointer must fall back to the latest valid approved version.');

    $config->values['brstunden'][DocumentTemplateSettingsService::CONFIG_KEY] = '{broken';
    assertSameValue(1, $settings->approved()['version'], 'Corrupt configuration must safely fall back to the bundled approved default.');

    echo "DocumentTemplateSettingsService tests passed\n";
}
