<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use OCA\BrStunden\AppInfo\Application;
use OCA\LocalBase\Service\AppLogger;

class BrStundenLogger {
    public function __construct(
        private AppLogger $logger
    ) {
    }

    public function error(string $action, \Throwable $e, array $context = []): void {
        $this->logger->error(Application::APP_ID, 'BRStunden', $action, $e, $context);
    }

    public function info(string $message, array $context = []): void {
        $this->logger->info(Application::APP_ID, $message, $context);
    }
}
