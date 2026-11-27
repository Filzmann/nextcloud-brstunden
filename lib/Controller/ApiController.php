<?php

declare(strict_types=1);

namespace OCA\BrStunden\Controller;

use OCA\BrStunden\AppInfo\Application;
use OCA\BrStunden\Service\BrMemberService;
use OCA\BrStunden\Service\AuthorizationService;
use OCA\BrStunden\Service\CapabilitySettingsService;
use OCA\BrStunden\Service\BrStundenLogger;
use OCA\BrStunden\Service\HoursService;
use OCA\BrStunden\Service\PayrollPdfService;
use OCA\BrStunden\Service\ReminderService;
use OCA\LocalBase\Controller\ApiResponder;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IL10N;
use OCP\IRequest;

class ApiController extends Controller {
    public function __construct(IRequest $request,
        private HoursService $hours,
        private ReminderService $reminders,
        private PayrollPdfService $payrollPdf,
        private BrMemberService $members,
        private AuthorizationService $authorization,
        private BrStundenLogger $logger,
        private ApiResponder $responder,
        private ?IL10N $l10n = null) {
        parent::__construct(Application::APP_ID, $request);
    }

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    #[NoAdminRequired]
    public function state(): DataResponse {
        return $this->responder->respond(
            fn(): array => $this->hours->state(),
            [$this->logger, 'error'],
            'state'
        );
    }

    #[NoAdminRequired]
    public function year(int $year): DataResponse {
        return $this->responder->respond(
            fn(): array => $this->hours->yearOverview($year),
            [$this->logger, 'error'],
            'year',
            ['year' => $year]
        );
    }

    #[NoAdminRequired]
    public function saveEntry(int $year, int $month, string $hours = '', string $fobiHours = '', string $note = '', ?string $userId = null): DataResponse {
        return $this->responder->respond(
            fn(): array => $this->hours->saveEntry($year, $month, $hours, $fobiHours, $note, $userId),
            [$this->logger, 'error'],
            'save_entry',
            ['year' => $year, 'month' => $month]
        );
    }

    #[NoAdminRequired]
    public function deleteEntry(int $year, int $month, ?string $userId = null): DataResponse {
        return $this->responder->respond(
            fn(): array => $this->hours->deleteEntry($year, $month, $userId),
            [$this->logger, 'error'],
            'delete_entry',
            ['year' => $year, 'month' => $month]
        );
    }

    #[NoAdminRequired]
    #[NoCSRFRequired]
    public function payrollPdf(int $year, int $month, ?string $userId = null): Response {
        try {
            $pdf = $this->payrollPdf->createForUser($userId ?? $this->members->currentUserId(), $year, $month);

            return new DataDownloadResponse($pdf['content'], $pdf['filename'], $pdf['contentType']);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['ok' => false, 'message' => $this->t($e->getMessage())], Http::STATUS_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new DataResponse(['ok' => false, 'message' => $this->t($e->getMessage())], Http::STATUS_FORBIDDEN);
        } catch (\Throwable $e) {
            $this->logger->error('payroll_pdf', $e, ['year' => $year, 'month' => $month]);

            return new DataResponse([
                'ok' => false,
                'message' => $this->t('Die Abrechnung konnte nicht erzeugt werden. Details stehen im Nextcloud-Log.'),
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    #[NoAdminRequired]
    public function reminderPreview(): DataResponse {
        return $this->responder->respond(function(): array {
            $this->authorization->assertCan(CapabilitySettingsService::REMINDER_DASHBOARD);

            return $this->reminders->previewMonthlyReminders();
        }, [$this->logger, 'error'], 'reminder_preview');
    }
}
