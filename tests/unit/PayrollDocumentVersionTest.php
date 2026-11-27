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
    use OCA\BrStunden\Model\HourEntry;
    use OCA\BrStunden\Service\AuthorizationService;
    use OCA\BrStunden\Service\BrMemberService;
    use OCA\BrStunden\Service\CalendarService;
    use OCA\BrStunden\Service\DocumentTemplateSettingsService;
    use OCA\BrStunden\Service\PayrollPdfService;
    use OCA\BrStunden\Store\HourEntryStore;
    use OCP\IAppConfig;
    use function OCA\BrStunden\Tests\assertSameValue;

    $calendar = new class extends CalendarService {
        public function today(): DateTimeImmutable { return new DateTimeImmutable('2026-07-03'); }
    };
    $members = new class extends BrMemberService {
        public function __construct() {}
        public function currentUserId(): string { return 'member'; }
        public function members(): array { return [['uid' => 'member', 'displayName' => 'Mitglied (Test)', 'email' => '']]; }
        public function addressForUser(string $uid): string { return 'Beispielweg 2'; }
    };
    $entries = new class extends HourEntryStore {
        public function __construct() {}
        public function findForUserMonth(string $userId, int $year, int $month): ?HourEntry {
            return new HourEntry(1, $userId, $year, $month, 90, 30, '', $userId, '2026-07-03', '2026-07-03');
        }
    };
    $authorization = new class extends AuthorizationService {
        public function __construct() {}
        public function assertCanExportPayrollFor(string $targetUid): void {}
    };
    $config = new class implements IAppConfig {
        public array $values = [];
        public function getValueString(string $appId, string $key, string $default = ''): string { return $this->values[$appId][$key] ?? $default; }
        public function setValueString(string $appId, string $key, string $value): void { $this->values[$appId][$key] = $value; }
    };
    $templates = new DocumentTemplateSettingsService($config, new class implements \OCP\IConfig {
        public function getSystemValueString(string $key, string $default = ''): string { return $key === 'locale' ? 'de-DE' : $default; }
    });
    $service = new PayrollPdfService($members, $entries, $calendar, $authorization, $templates);

    $first = $service->createForCurrentUser(2026, 7);
    assertSameValue(1, $first['templateVersion'], 'Generated payroll metadata must expose template version 1.');
    assertSameValue('de-DE', $first['outputLocale'], 'Generated payroll metadata must expose its output locale.');
    assertSameValue(true, str_contains($first['content'], 'Vorlage v1 / de-DE'), 'The PDF itself must retain template version and output locale.');
    assertSameValue(true, str_contains($first['content'], '/DCTDecode'), 'Version 1 must preserve the existing approved header image.');

    $templates->saveDraft([
        'organizationName' => 'Beispiel Organisation e.V.',
        'organizationAddress' => "Musterweg 4\n12345 Beispielstadt",
        'issuePlace' => 'Beispielstadt',
        'introText' => 'Zeitraum {{month_label}} fuer {{member_name}}',
        'footerText' => 'Referenz {{template_version}} ({{output_locale}})',
    ]);
    $stillApproved = $service->createForCurrentUser(2026, 7);
    assertSameValue(1, $stillApproved['templateVersion'], 'An unapproved draft must never change generated payrolls.');

    $templates->approveDraft();
    $second = $service->createForCurrentUser(2026, 7);
    assertSameValue(2, $second['templateVersion'], 'A newly approved template must be used by subsequent payrolls.');
    assertSameValue(true, str_contains($second['content'], 'Beispiel Organisation e.V.'), 'Approved master data must be rendered.');
    assertSameValue(true, str_contains($second['content'], 'Zeitraum Juli 2026 fuer Mitglied \\(Test\\)'), 'Resolved text must be PDF-escaped instead of interpreted.');
    assertSameValue(true, str_contains($second['content'], 'Referenz 2 \\(de-DE\\)'), 'Configured footer placeholders must resolve to inert escaped text.');
    assertSameValue(true, str_contains($second['content'], '(1,5) Tj'), 'The fixed BR-hours calculation must remain unchanged.');
    assertSameValue(true, str_contains($second['content'], '(0,5) Tj'), 'The fixed training-hours calculation must remain unchanged.');
    assertSameValue(true, str_contains($second['content'], '(2) Tj'), 'The fixed total-hours calculation must remain unchanged.');
    assertSameValue(true, str_contains($first['content'], 'Vorlage v1 / de-DE'), 'An already generated document must retain its original version marker.');
    $reproducedFirst = $service->createForUserUsingVersion('member', 2026, 7, 1);
    assertSameValue($first['content'], $reproducedFirst['content'], 'A retained approved version must reproduce the historical PDF deterministically for unchanged source data.');

    echo "Payroll document version tests passed\n";
}
