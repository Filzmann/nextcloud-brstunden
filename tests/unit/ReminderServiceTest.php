<?php

declare(strict_types=1);

namespace {
    if (!interface_exists(\OCP\IURLGenerator::class)) {
        eval('namespace OCP; interface IURLGenerator { public function linkToRouteAbsolute($routeName, $arguments = []): string; }');
    }
    if (!interface_exists(\OCP\Mail\IMailer::class)) {
        eval('namespace OCP\Mail; interface IMailer { public function createMessage(): object; public function send($message): array; }');
    }

    require_once __DIR__ . '/helpers.php';
    require_once __DIR__ . '/../../lib/Service/CalendarService.php';
    require_once __DIR__ . '/../../lib/Service/BrMemberService.php';
    require_once __DIR__ . '/../../lib/Service/HoursService.php';
    require_once __DIR__ . '/../../lib/Store/ReminderRunStore.php';
    require_once __DIR__ . '/../../lib/Service/BrStundenLogger.php';
    require_once __DIR__ . '/../../lib/Service/ReminderService.php';

    use OCA\BrStunden\Service\BrMemberService;
    use OCA\BrStunden\Service\BrStundenLogger;
    use OCA\BrStunden\Service\CalendarService;
    use OCA\BrStunden\Service\HoursService;
    use OCA\BrStunden\Service\ReminderService;
    use OCA\BrStunden\Store\ReminderRunStore;
    use OCP\IURLGenerator;
    use OCP\Mail\IMailer;
    use function OCA\BrStunden\Tests\assertSameValue;

    $members = new class extends BrMemberService {
        public function __construct() {
        }

        public function members(): array {
            return [
                ['uid' => 'simon', 'displayName' => 'Simon Test', 'email' => 'simon@example.invalid'],
                ['uid' => 'alex', 'displayName' => 'Alex Test', 'email' => ''],
                ['uid' => 'nina', 'displayName' => '', 'email' => 'nina@example.invalid'],
            ];
        }
    };

    $hours = new class extends HoursService {
        public array $missingByUid = [
            'simon' => [
                ['month' => 2, 'label' => 'Februar'],
                ['month' => 7, 'label' => 'Juli'],
            ],
            'alex' => [
                ['month' => 1, 'label' => 'Januar'],
            ],
            'nina' => [],
        ];

        public function __construct() {
        }

        public function missingMonthsForUser(string $userId, int $year, int $untilMonth): array {
            assertSameValue(2026, $year, 'Reminder preview should use the selected year.');
            assertSameValue(7, $untilMonth, 'Reminder preview should use the selected month.');

            return $this->missingByUid[$userId] ?? [];
        }
    };

    $calendar = new class extends CalendarService {
        public function today(): DateTimeImmutable {
            return new DateTimeImmutable('2026-07-31');
        }
    };

    $runs = new class extends ReminderRunStore {
        public bool $beginResult = true;
        public array $begun = [];
        public array $finished = [];

        public function __construct() {
        }

        public function begin(int $year, int $month): bool {
            $this->begun[] = compact('year', 'month');

            return $this->beginResult;
        }

        public function finish(int $year, int $month, int $sent, int $skipped, int $failed): void {
            $this->finished[] = compact('year', 'month', 'sent', 'skipped', 'failed');
        }
    };

    $mailer = new class implements IMailer {
        public array $messages = [];
        public bool $failSend = false;

        public function createMessage(): object {
            $message = new class {
                public array $to = [];
                public string $subject = '';
                public string $body = '';

                public function setTo(array $to): void {
                    $this->to = $to;
                }

                public function setSubject(string $subject): void {
                    $this->subject = $subject;
                }

                public function setPlainBody(string $body): void {
                    $this->body = $body;
                }
            };
            $this->messages[] = $message;

            return $message;
        }

        public function send($message): array {
            if ($this->failSend) {
                throw new RuntimeException('Mail transport failed.');
            }

            return [];
        }
    };

    $urlGenerator = new class implements IURLGenerator {
        public array $routes = [];

        public function linkToRouteAbsolute($routeName, $arguments = []): string {
            $this->routes[] = compact('routeName', 'arguments');

            return 'https://nextcloud.example.invalid/apps/brstunden/';
        }
    };

    $logger = new class extends BrStundenLogger {
        public array $errors = [];

        public function __construct() {
        }

        public function error(string $action, Throwable $e, array $context = []): void {
            $this->errors[] = compact('action', 'context');
        }

        public function info(string $message, array $context = []): void {
        }
    };

    $service = new ReminderService($members, $hours, $calendar, $runs, $mailer, $urlGenerator, $logger);

    $preview = $service->previewMonthlyReminders(new DateTimeImmutable('2026-07-31'));
    assertSameValue(2026, $preview['year'], 'Preview should expose the reminder year.');
    assertSameValue(7, $preview['untilMonth'], 'Preview should expose the reminder month.');
    assertSameValue(true, $preview['isLastDayOfMonth'], 'Preview should expose whether today is month end.');
    assertSameValue(
        ['members' => 3, 'willSend' => 1, 'missingMonths' => 3],
        $preview['summary'],
        'Preview summary should count recipients and missing months.'
    );
    assertSameValue(true, $preview['members'][0]['willSend'], 'Members with email and missing months should receive reminders.');
    assertSameValue(false, $preview['members'][1]['hasEmail'], 'Preview should expose missing email addresses.');
    assertSameValue('nina', $preview['members'][2]['displayName'], 'Preview should fall back to uid when display name is empty.');

    $nonMonthEndResult = $service->sendMonthlyReminders(new DateTimeImmutable('2026-07-30'));
    assertSameValue(
        ['ran' => false, 'alreadyRan' => false, 'sent' => 0, 'skipped' => 0, 'failed' => 0],
        $nonMonthEndResult,
        'Monthly reminders should not run before month end.'
    );
    assertSameValue([], $runs->begun, 'Non-month-end runs should not acquire the monthly run lock.');

    $runs->beginResult = false;
    $alreadyRan = $service->sendMonthlyReminders(new DateTimeImmutable('2026-07-31'));
    assertSameValue(true, $alreadyRan['alreadyRan'], 'Existing monthly runs should be reported.');
    assertSameValue(0, $alreadyRan['sent'], 'Already-run reminders should not send mail.');
    assertSameValue([], $mailer->messages, 'Already-run reminders should not create messages.');

    $runs->beginResult = true;
    $runs->begun = [];
    $runs->finished = [];
    $sentResult = $service->sendMonthlyReminders(new DateTimeImmutable('2026-07-31'));
    assertSameValue(
        ['ran' => true, 'alreadyRan' => false, 'year' => 2026, 'month' => 7, 'sent' => 1, 'skipped' => 2, 'failed' => 0],
        $sentResult,
        'Month-end reminders should send only deliverable missing-month reminders.'
    );
    assertSameValue([['year' => 2026, 'month' => 7]], $runs->begun, 'Month-end reminders should lock one run.');
    assertSameValue(
        [['year' => 2026, 'month' => 7, 'sent' => 1, 'skipped' => 2, 'failed' => 0]],
        $runs->finished,
        'Month-end reminders should finish the run with counters.'
    );
    assertSameValue(['simon@example.invalid' => 'Simon Test'], $mailer->messages[0]->to, 'Reminder mail should target the member address.');
    assertSameValue('BR-Stunden: fehlende Monate eintragen', $mailer->messages[0]->subject, 'Reminder mail should use the expected subject.');
    assertSameValue(true, str_contains($mailer->messages[0]->body, 'Februar, Juli'), 'Reminder mail should list missing month labels.');
    assertSameValue(true, str_contains($mailer->messages[0]->body, 'https://nextcloud.example.invalid/apps/brstunden/'), 'Reminder mail should contain the app URL.');
    assertSameValue([['routeName' => 'brstunden.page.index', 'arguments' => []]], $urlGenerator->routes, 'Reminder mail should link to the BRStunden page.');

    $mailer->failSend = true;
    $mailer->messages = [];
    $runs->begun = [];
    $runs->finished = [];
    $failedResult = $service->sendMonthlyReminders(new DateTimeImmutable('2026-07-31'));
    assertSameValue(0, $failedResult['sent'], 'Failed reminders should not count as sent.');
    assertSameValue(1, $failedResult['failed'], 'Failed reminders should count failures.');
    assertSameValue(
        [['year' => 2026, 'month' => 7, 'sent' => 0, 'skipped' => 2, 'failed' => 1]],
        $runs->finished,
        'Failed reminders should still finish the run with failure counters.'
    );
    assertSameValue('send_monthly_reminder', $logger->errors[0]['action'], 'Failed reminders should be logged.');
    assertSameValue(['uid' => 'simon', 'year' => 2026], $logger->errors[0]['context'], 'Failure logs should include safe context.');

    echo 'ReminderService tests passed' . PHP_EOL;
}
