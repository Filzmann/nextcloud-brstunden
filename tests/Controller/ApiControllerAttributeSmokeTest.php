<?php

declare(strict_types=1);

namespace {
    if (!class_exists(\OCP\AppFramework\Controller::class)) {
        eval('namespace OCP\AppFramework; class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} }');
    }
    if (!interface_exists(\OCP\IRequest::class)) {
        eval('namespace OCP; interface IRequest {}');
    }
    if (!class_exists(\OCP\AppFramework\Http::class)) {
        eval('namespace OCP\AppFramework; class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; public const STATUS_INTERNAL_SERVER_ERROR = 500; }');
    }
    if (!class_exists(\OCP\AppFramework\Http\Response::class)) {
        eval('namespace OCP\AppFramework\Http; class Response {}');
    }
    if (!class_exists(\OCP\AppFramework\Http\DataResponse::class)) {
        eval('namespace OCP\AppFramework\Http; class DataResponse extends Response { public function __construct(mixed $data = [], int $status = 200) {} }');
    }
    if (!class_exists(\OCP\AppFramework\Http\DataDownloadResponse::class)) {
        eval('namespace OCP\AppFramework\Http; class DataDownloadResponse extends Response { public function __construct(string $data, string $filename, string $contentType) {} }');
    }
    if (!class_exists(\OCP\AppFramework\Http\Attribute\NoAdminRequired::class)) {
        eval('namespace OCP\AppFramework\Http\Attribute; #[\Attribute(\Attribute::TARGET_METHOD)] class NoAdminRequired {}');
    }
    if (!class_exists(\OCP\AppFramework\Http\Attribute\NoCSRFRequired::class)) {
        eval('namespace OCP\AppFramework\Http\Attribute; #[\Attribute(\Attribute::TARGET_METHOD)] class NoCSRFRequired {}');
    }
}

namespace OCA\BrStunden\AppInfo {
    if (!class_exists(Application::class)) {
        final class Application {
            public const APP_ID = 'brstunden';
        }
    }
}

namespace {
    require __DIR__ . '/../../lib/Controller/ApiController.php';

    use OCA\BrStunden\Controller\ApiController;
    use OCP\AppFramework\Http\Attribute\NoAdminRequired;
    use OCP\AppFramework\Http\Attribute\NoCSRFRequired;

    $payrollPdf = new \ReflectionMethod(ApiController::class, 'payrollPdf');

    if ($payrollPdf->getAttributes(NoAdminRequired::class) === []) {
        throw new \RuntimeException('payrollPdf should be available to regular users.');
    }

    if ($payrollPdf->getAttributes(NoCSRFRequired::class) === []) {
        throw new \RuntimeException('payrollPdf is a direct browser download and must not require a CSRF header.');
    }

    echo 'ApiController attribute smoke tests passed' . PHP_EOL;
}
