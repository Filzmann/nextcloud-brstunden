<?php

declare(strict_types=1);

namespace OCA\BrStunden\Controller;

use DomainException;
use OCA\BrStunden\AppInfo\Application;
use OCA\BrStunden\Service\DocumentTemplateSettingsService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\DataResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;
use Throwable;

final class DocumentSettingsController extends Controller {
    public function __construct(IRequest $request,
        private DocumentTemplateSettingsService $settings,
        private LoggerInterface $logger,
        private ?IL10N $l10n = null) {
        parent::__construct(Application::APP_ID, $request);
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    /** @param array<string,mixed> $document */
    public function saveDraft(array $document): DataResponse {
        return $this->execute(fn(): array => $this->settings->saveDraft($document), 'save_draft');
    }

    public function approve(): DataResponse {
        return $this->execute(fn(): array => $this->settings->approveDraft(), 'approve');
    }

    /** @param callable():array<string,mixed> $operation */
    private function execute(callable $operation, string $operationName): DataResponse {
        try {
            return new DataResponse(['ok' => true, 'state' => $operation()]);
        } catch (DomainException $error) {
            return new DataResponse(['ok' => false, 'message' => $this->t($error->getMessage())], 422);
        } catch (Throwable $error) {
            $this->logger->error('BR-Stunden document settings failed', [
                'app' => Application::APP_ID,
                'operation' => $operationName,
                'exceptionClass' => $error::class,
            ]);

            return new DataResponse(
                ['ok' => false, 'message' => $this->t('Die Dokumentkonfiguration konnte nicht verarbeitet werden.')],
                Http::STATUS_INTERNAL_SERVER_ERROR,
            );
        }
    }
}
