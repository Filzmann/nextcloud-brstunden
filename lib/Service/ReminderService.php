<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use DateTimeImmutable;
use OCA\BrStunden\Store\ReminderRunStore;
use OCP\IURLGenerator;
use OCP\Mail\IMailer;

class ReminderService {
    public function __construct(
        private BrMemberService $members,
        private HoursService $hours,
        private CalendarService $calendar,
        private ReminderRunStore $runs,
        private IMailer $mailer,
        private IURLGenerator $urlGenerator,
        private BrStundenLogger $logger
    ) {
    }

    public function sendMonthlyReminders(?DateTimeImmutable $today = null): array {
        $today ??= $this->calendar->today();
        if (!$this->calendar->isLastDayOfMonth($today)) {
            return ['ran' => false, 'alreadyRan' => false, 'sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $year = (int)$today->format('Y');
        $untilMonth = (int)$today->format('n');
        $preview = $this->previewMonthlyReminders($today);
        if (!$this->runs->begin($year, $untilMonth)) {
            return [
                'ran' => false,
                'alreadyRan' => true,
                'year' => $year,
                'month' => $untilMonth,
                'sent' => 0,
                'skipped' => 0,
                'failed' => 0,
            ];
        }

        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($preview['members'] as $member) {
            if (!$member['willSend']) {
                $skipped++;
                continue;
            }

            try {
                $this->sendReminder($member, $year, $member['missing']);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $this->logger->error('send_monthly_reminder', $e, ['uid' => $member['uid'], 'year' => $year]);
            }
        }

        $this->runs->finish($year, $untilMonth, $sent, $skipped, $failed);

        return [
            'ran' => true,
            'alreadyRan' => false,
            'year' => $year,
            'month' => $untilMonth,
            'sent' => $sent,
            'skipped' => $skipped,
            'failed' => $failed,
        ];
    }

    public function previewMonthlyReminders(?DateTimeImmutable $today = null): array {
        $today ??= $this->calendar->today();
        $year = (int)$today->format('Y');
        $untilMonth = (int)$today->format('n');
        $members = [];
        $willSend = 0;
        $missingTotal = 0;

        foreach ($this->members->members() as $member) {
            $email = (string)($member['email'] ?? '');
            $missing = $this->hours->missingMonthsForUser((string)$member['uid'], $year, $untilMonth);
            $canSend = $email !== '' && $missing !== [];
            if ($canSend) {
                $willSend++;
            }
            $missingTotal += count($missing);

            $members[] = [
                'uid' => (string)$member['uid'],
                'displayName' => (string)($member['displayName'] ?: $member['uid']),
                'email' => $email,
                'hasEmail' => $email !== '',
                'missing' => $missing,
                'willSend' => $canSend,
            ];
        }

        return [
            'year' => $year,
            'untilMonth' => $untilMonth,
            'isLastDayOfMonth' => $this->calendar->isLastDayOfMonth($today),
            'summary' => [
                'members' => count($members),
                'willSend' => $willSend,
                'missingMonths' => $missingTotal,
            ],
            'members' => $members,
        ];
    }

    private function sendReminder(array $member, int $year, array $missing): void {
        $labels = implode(', ', array_map(static fn(array $month): string => $month['label'], $missing));
        $url = $this->urlGenerator->linkToRouteAbsolute('brstunden.page.index');
        $displayName = (string)($member['displayName'] ?: $member['uid']);

        $body = "Hallo {$displayName},\n\n"
            . "fuer die BR-Stunden {$year} fehlen bei dir noch folgende Monate:\n"
            . $labels . "\n\n"
            . "Bitte trage die Stunden in BR-Stunden nach:\n"
            . $url . "\n\n"
            . "Diese Erinnerung wurde automatisch am Monatsende erzeugt.";

        $message = $this->mailer->createMessage();
        $message->setTo([(string)$member['email'] => $displayName]);
        $message->setSubject('BR-Stunden: fehlende Monate eintragen');
        $message->setPlainBody($body);
        $this->mailer->send($message);
    }
}
