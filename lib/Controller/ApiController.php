<?php

declare(strict_types=1);

namespace OCA\BrStunden\Controller;

use OCA\BrStunden\AppInfo\Application;
use OCA\BrStunden\Service\BrMemberService;
use OCA\BrStunden\Service\BrStundenLogger;
use OCA\BrStunden\Service\HoursService;
use OCA\BrStunden\Service\PayrollPdfService;
use OCA\BrStunden\Service\ReminderService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\DataDownloadResponse;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;

class ApiController extends Controller {
    public function __construct(
        IRequest $request,
        private HoursService $hours,
        private ReminderService $reminders,
        private PayrollPdfService $payrollPdf,
        private BrMemberService $members,
        private BrStundenLogger $logger
    ) {
        parent::__construct(Application::APP_ID, $request);
    }

    #[NoAdminRequired]
    public function state(): DataResponse {
        return $this->respond(fn(): array => $this->hours->state(), 'state');
    }

    #[NoAdminRequired]
    public function year(int $year): DataResponse {
        return $this->respond(fn(): array => $this->hours->yearOverview($year), 'year', ['year' => $year]);
    }

    #[NoAdminRequired]
    public function saveEntry(int $year, int $month, string $hours = '', string $fobiHours = '', string $note = ''): DataResponse {
        return $this->respond(
            fn(): array => $this->hours->saveEntry($year, $month, $hours, $fobiHours, $note),
            'save_entry',
            ['year' => $year, 'month' => $month]
        );
    }

    #[NoAdminRequired]
    public function deleteEntry(int $year, int $month): DataResponse {
        return $this->respond(
            fn(): array => $this->hours->deleteEntry($year, $month),
            'delete_entry',
            ['year' => $year, 'month' => $month]
        );
    }

    #[NoAdminRequired]
    public function payrollPdf(int $year, int $month): Response {
        try {
            $pdf = $this->payrollPdf->createForCurrentUser($year, $month);

            return new DataDownloadResponse($pdf['content'], $pdf['filename'], $pdf['contentType']);
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['ok' => false, 'message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new DataResponse(['ok' => false, 'message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\Throwable $e) {
            $this->logger->error('payroll_pdf', $e, ['year' => $year, 'month' => $month]);

            return new DataResponse([
                'ok' => false,
                'message' => 'Die Abrechnung konnte nicht erzeugt werden. Details stehen im Nextcloud-Log.',
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }

    #[NoAdminRequired]
    public function reminderPreview(): DataResponse {
        return $this->respond(function(): array {
            $this->members->assertCurrentUserIsMember();

            return $this->reminders->previewMonthlyReminders();
        }, 'reminder_preview');
    }

    private function respond(callable $callback, string $action, array $context = []): DataResponse {
        try {
            return new DataResponse($callback());
        } catch (\InvalidArgumentException $e) {
            return new DataResponse(['ok' => false, 'message' => $e->getMessage()], Http::STATUS_BAD_REQUEST);
        } catch (\DomainException $e) {
            return new DataResponse(['ok' => false, 'message' => $e->getMessage()], Http::STATUS_FORBIDDEN);
        } catch (\Throwable $e) {
            $this->logger->error($action, $e, $context);

            return new DataResponse([
                'ok' => false,
                'message' => 'Die Aktion konnte nicht ausgefuehrt werden. Details stehen im Nextcloud-Log.',
            ], Http::STATUS_INTERNAL_SERVER_ERROR);
        }
    }
}
