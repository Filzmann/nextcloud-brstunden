<?php

declare(strict_types=1);

use OCA\BrStunden\Model\HourEntry;
use OCA\BrStunden\Service\AuthorizationService;
use OCA\BrStunden\Service\BrMemberService;
use OCA\BrStunden\Service\CalendarService;
use OCA\BrStunden\Service\PayrollPdfService;
use OCA\BrStunden\Store\HourEntryStore;
use function OCA\BrStunden\Tests\assertSameValue;

$calendar = new class extends CalendarService {
    public function today(): DateTimeImmutable { return new DateTimeImmutable('2026-07-03'); }
};
$members = new class extends BrMemberService {
    public function __construct() {}
    public function currentUserId(): string { return 'chair'; }
    public function members(): array {
        return [
            ['uid' => 'chair', 'displayName' => 'Vorsitz Test', 'email' => ''],
            ['uid' => 'member', 'displayName' => 'Mitglied Test', 'email' => ''],
        ];
    }
    public function addressForUser(string $uid): string { return $uid === 'member' ? 'Beispielweg 2' : 'Beispielweg 1'; }
};
$entries = new class extends HourEntryStore {
    public array $lookups = [];
    public function __construct() {}
    public function findForUserMonth(string $userId, int $year, int $month): ?HourEntry {
        $this->lookups[] = compact('userId', 'year', 'month');
        return new HourEntry(1, $userId, $year, $month, 90, 30, '', 'member', '2026-07-03', '2026-07-03');
    }
};
$authorization = new class extends AuthorizationService {
    public bool $deny = false;
    public array $targets = [];
    public function __construct() {}
    public function assertCanExportPayrollFor(string $targetUid): void {
        $this->targets[] = $targetUid;
        if ($this->deny) throw new \DomainException('denied');
    }
};

$service = new PayrollPdfService($members, $entries, $calendar, $authorization);
$pdf = $service->createForUser('member', 2026, 7);
assertSameValue('br-stunden-abrechnung-member-2026-07.pdf', $pdf['filename'], 'Authorized chair/deputy export must target the selected member.');
assertSameValue(true, str_contains($pdf['content'], 'Mitglied Test'), 'The foreign payroll must contain the target member name.');
assertSameValue(true, str_contains($pdf['content'], 'Beispielweg 2'), 'The foreign payroll must contain the target member address.');

$authorization->deny = true;
$lookupsBefore = $entries->lookups;
try {
    $service->createForUser('outsider', 2026, 7);
} catch (\DomainException) {
}
assertSameValue($lookupsBefore, $entries->lookups, 'A rejected payroll target must not be read from persistence.');

echo "PayrollPdfService tests passed\n";
