<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use OCA\BrStunden\AppInfo\Application;
use Psr\Log\LoggerInterface;

class BrStundenLogger {
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function error(string $action, \Throwable $e, array $context = []): void {
        $context['app'] = Application::APP_ID;
        $context['action'] = $action;
        $context['exception'] = $e;
        $this->logger->error($e->getMessage(), $context);
    }

    public function info(string $message, array $context = []): void {
        $context['app'] = Application::APP_ID;
        $this->logger->info($message, $context);
    }
}
