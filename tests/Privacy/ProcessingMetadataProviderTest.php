<?php

declare(strict_types=1);

namespace OCP\AppFramework {
    class App { public function __construct(string $appName, array $urlParams = []) {} }
}

namespace OCP\AppFramework\Bootstrap {
    interface IBootContext {}
    interface IBootstrap {}
    interface IRegistrationContext {}
}

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}

namespace {
    use OCA\BrStunden\Privacy\BrStundenProcessingMetadataProvider;
    use OCA\BrStunden\Privacy\BrStundenProcessingMetadataProviderListener;
    use OCA\FlzDataProtection\PublicApi\V1\RegisterProcessingMetadataProvidersEvent;
    use OCP\EventDispatcher\Event;

    $provider = new BrStundenProcessingMetadataProvider();
    $catalog = $provider->catalog();
    $descriptor = $provider->descriptor();
    if ($descriptor->appId() !== 'brstunden' || $descriptor->displayName() !== 'BR-Stunden' || $descriptor->contractVersion() !== '1.0') {
        throw new RuntimeException('Der Processing-Metadata-Provider beschreibt BR-Stunden nicht korrekt.');
    }
    if ($catalog->appId() !== 'brstunden') {
        throw new RuntimeException('Processing-Metadata-Provider und Katalog verwenden nicht die kanonische App-ID.');
    }
    if ($catalog->processingIds() !== [
        'monthly_hours_management',
        'monthly_reminder_communication',
        'payroll_pdf_generation',
    ]) {
        throw new RuntimeException('Der app-lokale Processing-Katalog ist unvollständig.');
    }
    if (array_key_exists('personal_runtime_data', $catalog->toArray())) {
        throw new RuntimeException('Der Processing-Katalog enthält personenbezogene Laufzeitdaten.');
    }

    $registration = new RegisterProcessingMetadataProvidersEvent();
    $listener = new BrStundenProcessingMetadataProviderListener($provider);
    $listener->handle(new Event());
    if ($registration->providers() !== []) {
        throw new RuntimeException('Ein fremdes Event registriert den Processing-Metadata-Provider.');
    }
    $listener->handle($registration);
    if (($registration->providers()['brstunden'] ?? null) !== $provider) {
        throw new RuntimeException('Der Processing-Metadata-Provider wird nicht lazy registriert.');
    }

    $application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(RegisterProcessingMetadataProvidersEvent::class, BrStundenProcessingMetadataProviderListener::class)')) {
        throw new RuntimeException('Der Bootstrap registriert den Processing-Metadata-Provider nicht am öffentlichen V1-Event.');
    }

    echo "BR-Stunden processing metadata provider test passed\n";
}
