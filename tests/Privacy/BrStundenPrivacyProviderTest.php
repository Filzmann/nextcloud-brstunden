<?php

declare(strict_types=1);

namespace OCP\EventDispatcher {
    class Event { public function __construct() {} }
    interface IEventListener { public function handle(Event $event): void; }
}

namespace OCA\BrStunden\Repository {
    class HourEntryRepository {
        /** @var list<array<string, mixed>> */ public array $items = [];
        /** @return list<array<string, mixed>> */
        public function findPrivacyEntriesForSubject(string $uid, int $limit): array {
            return array_slice(array_values(array_filter(
                $this->items,
                static fn(array $item): bool => $item['user_id'] === $uid || $item['updated_by_uid'] === $uid,
            )), 0, $limit);
        }
    }
}

namespace {
    use OCA\BrStunden\Privacy\BrStundenPersonalDataProvider;
    use OCA\BrStunden\Privacy\BrStundenPrivacyProviderListener;
    use OCA\BrStunden\Repository\HourEntryRepository;
    use OCA\FilzmannDataProtection\PublicApi\V1\DataSubjectRef;
    use OCA\FilzmannDataProtection\PublicApi\V1\PersonalDataRequest;
    use OCA\FilzmannDataProtection\PublicApi\V1\RegisterPersonalDataProvidersEvent;

    $entries = new HourEntryRepository();
    $entries->items = [
        ['id'=>1, 'user_id'=>'self', 'entry_year'=>2026, 'entry_month'=>7, 'minutes'=>120, 'fobi_minutes'=>30, 'note'=>'Vorbereitung mit Kollegin', 'updated_by_uid'=>'self', 'created_at'=>new DateTimeImmutable('2026-07-31'), 'updated_at'=>new DateTimeImmutable('2026-08-01')],
        ['id'=>2, 'user_id'=>'other-person', 'entry_year'=>2026, 'entry_month'=>6, 'minutes'=>600, 'fobi_minutes'=>60, 'note'=>'Fremde vertrauliche Notiz', 'updated_by_uid'=>'self', 'created_at'=>'2026-06-30', 'updated_at'=>'2026-07-01'],
        ['id'=>3, 'user_id'=>'other-person', 'entry_year'=>2026, 'entry_month'=>5, 'minutes'=>300, 'fobi_minutes'=>0, 'note'=>'Noch eine fremde Notiz', 'updated_by_uid'=>'other-person', 'created_at'=>'2026-05-31', 'updated_at'=>'2026-06-01'],
    ];
    $provider = new BrStundenPersonalDataProvider($entries);
    $descriptor = $provider->descriptor();
    if ($descriptor->appId() !== 'brstunden' || $descriptor->contractVersion() !== '1.0' || !$descriptor->supportsSubjectType('nextcloud-user')) {
        throw new RuntimeException('BRStunden beschreibt den Standalone-V1-Vertrag nicht korrekt.');
    }

    $subject = new DataSubjectRef('nextcloud-user', 'self');
    $page = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 50, []));
    if ($page->status() !== 'complete' || count($page->entries()) !== 2) {
        throw new RuntimeException('BRStunden liefert nicht exakt die eigenen Datensätze und Bearbeitungsbezüge.');
    }
    $payload = array_map(static fn($entry): array => [
        'categoryId'=>$entry->categoryId(), 'summary'=>$entry->summary(), 'attributes'=>$entry->attributes(),
        'thirdPartyContentNotice'=>$entry->thirdPartyContentNotice(),
    ], $page->entries());
    $own = array_values(array_filter($payload, static fn(array $item): bool => $item['categoryId'] === 'hour_entry'))[0] ?? null;
    $activity = array_values(array_filter($payload, static fn(array $item): bool => $item['categoryId'] === 'hour_entry_activity'))[0] ?? null;
    if ($own === null || $activity === null) throw new RuntimeException('Eigener Stundensatz und Bearbeitungsnachweis werden nicht getrennt ausgewiesen.');
    $ownJson = json_encode($own, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    foreach (['Juli 2026', '2 Stunden', '0,5 Stunden', 'Vorbereitung mit Kollegin'] as $expected) {
        if (!str_contains($ownJson, $expected)) throw new RuntimeException("Eigene Stundenangabe fehlt: {$expected}");
    }
    if (!str_contains((string)$own['thirdPartyContentNotice'], 'andere Personen')) throw new RuntimeException('Die freie eigene Notiz ist nicht als möglicher Drittpersoneninhalt gekennzeichnet.');
    $activityJson = json_encode($activity, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    foreach (['other-person', '600', '60', 'Fremde vertrauliche Notiz'] as $forbidden) {
        if (str_contains($activityJson, $forbidden)) throw new RuntimeException("Bearbeitungsnachweis verrät fremde Daten: {$forbidden}");
    }
    if (!str_contains($activityJson, 'Juni 2026')) throw new RuntimeException('Bearbeitungsnachweis verliert den erforderlichen zeitlichen Kontext.');

    $limited = $provider->collect(new PersonalDataRequest($subject, 'de', 'access-report', 1, []));
    if ($limited->status() !== 'partial' || count($limited->entries()) !== 1 || $limited->restrictions() === []) {
        throw new RuntimeException('Ein begrenzter BRStunden-Bericht behauptet Vollständigkeit.');
    }
    $unsupported = $provider->collect(new PersonalDataRequest(new DataSubjectRef('external-person', 'self'), 'de', 'access-report', 50, []));
    if ($unsupported->status() !== 'not_applicable' || $unsupported->entries() !== []) throw new RuntimeException('Ein fremder Subject-Typ erhält BRStunden-Daten.');
    try {
        $provider->collect((new PersonalDataRequest($subject, 'de', 'access-report', 50, ['brstunden'=>'opaque']))->forProvider('brstunden', 50));
        throw new RuntimeException('Ein unbekannter Provider-Cursor wurde akzeptiert.');
    } catch (InvalidArgumentException) {}

    $listener = new BrStundenPrivacyProviderListener($provider);
    $registry = new RegisterPersonalDataProvidersEvent();
    $listener->handle($registry);
    if (array_keys($registry->providers()) !== ['brstunden']) throw new RuntimeException('BRStunden registriert seinen Privacy-Provider nicht.');
    $application = (string)file_get_contents(dirname(__DIR__, 2) . '/lib/AppInfo/Application.php');
    if (!str_contains($application, 'registerEventListener(RegisterPersonalDataProvidersEvent::class, BrStundenPrivacyProviderListener::class)')) {
        throw new RuntimeException('BRStunden registriert den Provider nicht am Standalone-V1-Event.');
    }

    echo "BRStunden privacy provider test passed\n";
}
