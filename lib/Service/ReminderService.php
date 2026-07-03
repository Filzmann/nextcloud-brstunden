<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use DateTimeImmutable;
use OCP\IURLGenerator;
use OCP\Mail\IMailer;

class ReminderService {
    public function __construct(
        private BrMemberService $members,
        private HoursService $hours,
        private IMailer $mailer,
        private IURLGenerator $urlGenerator,
        private BrStundenLogger $logger
    ) {
    }

    public function sendMonthlyReminders(?DateTimeImmutable $today = null): array {
        $today ??= new DateTimeImmutable('today');
        if ($today->format('j') !== $today->format('t')) {
            return ['ran' => false, 'sent' => 0, 'skipped' => 0, 'failed' => 0];
        }

        $year = (int)$today->format('Y');
        $untilMonth = (int)$today->format('n');
        $sent = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($this->members->members() as $member) {
            $email = (string)($member['email'] ?? '');
            if ($email === '') {
                $skipped++;
                continue;
            }

            $missing = $this->hours->missingMonthsForUser((string)$member['uid'], $year, $untilMonth);
            if ($missing === []) {
                $skipped++;
                continue;
            }

            try {
                $this->sendReminder($member, $year, $missing);
                $sent++;
            } catch (\Throwable $e) {
                $failed++;
                $this->logger->error('send_monthly_reminder', $e, ['uid' => $member['uid'], 'year' => $year]);
            }
        }

        return ['ran' => true, 'sent' => $sent, 'skipped' => $skipped, 'failed' => $failed];
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
