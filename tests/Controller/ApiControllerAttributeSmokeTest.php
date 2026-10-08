<?php

declare(strict_types=1);

namespace OCP { interface IRequest {} }
namespace OCP\AppFramework { class Controller { public function __construct(string $appName, \OCP\IRequest $request) {} } class Http { public const STATUS_BAD_REQUEST = 400; public const STATUS_FORBIDDEN = 403; public const STATUS_INTERNAL_SERVER_ERROR = 500; } }
namespace OCP\AppFramework\Http { class Response {} class DataResponse extends Response { public function __construct(mixed $data = [], int $status = 200) {} } class DataDownloadResponse extends Response { public function __construct(string $data, string $filename, string $contentType) {} } }
namespace OCP\AppFramework\Http\Attribute { #[\Attribute(\Attribute::TARGET_METHOD)] class NoAdminRequired {} #[\Attribute(\Attribute::TARGET_METHOD)] class NoCSRFRequired {} }
namespace {
}

namespace OCA\BrStunden\AppInfo {
    if (!class_exists(Application::class, false)) {
        final class Application {
            public const APP_ID = 'brstunden';
        }
    }
}

namespace {

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
