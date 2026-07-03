<?php

declare(strict_types=1);

namespace OCA\BrStunden\BackgroundJob;

use OCA\BrStunden\Service\BrStundenLogger;
use OCA\BrStunden\Service\ReminderService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class MonthlyReminderJob extends TimedJob {
    public function __construct(
        ITimeFactory $time,
        private ReminderService $reminders,
        private BrStundenLogger $logger
    ) {
        parent::__construct($time);
        $this->setInterval(24 * 60 * 60);
    }

    protected function run($argument): void {
        try {
            $result = $this->reminders->sendMonthlyReminders();
            $this->logger->info('monthly_reminder_job', $result);
        } catch (\Throwable $e) {
            $this->logger->error('monthly_reminder_job', $e);
        }
    }
}
