<?php

declare(strict_types=1);

namespace OCA\BrStunden\Controller;

use DomainException;
use OCA\BrStunden\AppInfo\Application;
use OCA\BrStunden\Service\CapabilitySettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

final class CapabilitySettingsController extends Controller {
    public function __construct(IRequest $request,
        private CapabilitySettingsService $settings,
        private LoggerInterface $logger,
        private ?IL10N $l10n = null) {
        parent::__construct(Application::APP_ID, $request);
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    public function update(array $assignments): DataResponse {
        try {
            return new DataResponse(['ok' => true, 'assignments' => $this->settings->update($assignments)]);
        } catch (DomainException $error) {
            return new DataResponse(['ok' => false, 'message' => $this->t($error->getMessage())], 422);
        } catch (Throwable $error) {
            $this->logger->error('BR-Stunden capability settings update failed', [
                'app' => Application::APP_ID,
                'exceptionClass' => $error::class,
            ]);
            return new DataResponse(['ok' => false, 'message' => $this->t('Die Berechtigungszuordnung konnte nicht gespeichert werden.')], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
