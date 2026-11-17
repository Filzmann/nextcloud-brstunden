<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use DomainException;
use JsonException;
use OCA\BrStunden\AppInfo\Application;
use OCP\IAppConfig;

final class DocumentTemplateSettingsService {
    public const CONFIG_KEY = 'payroll_document_templates';
    public const OUTPUT_LOCALE = 'de-DE';

    /** @var list<string> */
    private const FIELDS = [
        'organizationName',
        'organizationAddress',
        'issuePlace',
        'introText',
        'footerText',
    ];

    /** @var list<string> */
    private const PLACEHOLDERS = [
        'organization_name',
        'organization_address',
        'issue_place',
        'issue_date',
        'month_label',
        'member_name',
        'member_address',
        'template_version',
        'output_locale',
    ];

    public function __construct(private IAppConfig $config) {}

    /** @return array<string,mixed> */
    public static function bundledApproved(): array {
        return self::bundledDocument();
    }

    /** @return array{approved:array<string,mixed>,draft:?array<string,mixed>,versions:list<array<string,mixed>>,allowedPlaceholders:list<string>} */
    public function state(): array {
        $state = $this->load();
        $versions = $this->validVersions($state);

        return [
            'approved' => $this->approvedFrom($state, $versions),
            'draft' => $this->validDocument($state['draft'] ?? null, true),
            'versions' => array_values($versions),
            'allowedPlaceholders' => self::PLACEHOLDERS,
        ];
    }

    /** @param array<string,mixed> $input */
    public function saveDraft(array $input): array {
        $state = $this->load();
        $versions = $this->validVersions($state);
        $highestVersion = $versions === [] ? 0 : max(array_keys($versions));
        $draft = $this->validDocument($state['draft'] ?? null, true);
        $version = $draft['version'] ?? ($highestVersion + 1);
        $document = $this->validateInput($input, (int)$version);

        $state = [
            'schemaVersion' => 1,
            'approvedVersion' => $this->approvedFrom($state, $versions)['version'],
            'versions' => $versions,
            'draft' => $document,
        ];
        $this->persist($state);

        return $this->state();
    }

    public function approveDraft(): array {
        $state = $this->load();
        $versions = $this->validVersions($state);
        $draft = $this->validDocument($state['draft'] ?? null, true);
        if ($draft === null) {
            throw new DomainException('Es gibt keinen validierten Entwurf zur Freigabe.');
        }
        $version = (int)$draft['version'];
        if (isset($versions[$version])) {
            throw new DomainException('Die Vorlagenversion wurde bereits freigegeben und kann nicht überschrieben werden.');
        }
        $versions[$version] = $draft;
        ksort($versions, SORT_NUMERIC);
        $this->persist([
            'schemaVersion' => 1,
            'approvedVersion' => $version,
            'versions' => $versions,
            'draft' => null,
        ]);

        return $this->state();
    }

    /** @return array<string,mixed> */
    public function approved(): array {
        $state = $this->load();

        return $this->approvedFrom($state, $this->validVersions($state));
    }

    /** @return array<string,mixed> */
    public function version(int $version): array {
        $versions = $this->validVersions($this->load());
        if (!isset($versions[$version])) {
            throw new DomainException('Die angeforderte freigegebene Vorlagenversion ist nicht verfügbar.');
        }

        return $versions[$version];
    }

    /** @param array<string,string> $values */
    public function resolveText(string $template, array $values): string {
        $this->validatePlaceholders($template);

        return (string)preg_replace_callback('/\{\{([a-z_]+)\}\}/', static function (array $match) use ($values): string {
            $name = $match[1];
            if (!array_key_exists($name, $values)) {
                throw new DomainException('Für den Platzhalter {{' . $name . '}} fehlt ein Wert.');
            }

            return $values[$name];
        }, $template);
    }

    /** @return array<string,mixed> */
    private function load(): array {
        $stored = $this->config->getValueString(Application::APP_ID, self::CONFIG_KEY, '');
        if ($stored === '') {
            return $this->defaultState();
        }
        try {
            $decoded = json_decode($stored, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return $this->defaultState();
        }

        return is_array($decoded) ? $decoded : $this->defaultState();
    }

    /** @param array<string,mixed> $state @return array<int,array<string,mixed>> */
    private function validVersions(array $state): array {
        $versions = [];
        $storedVersions = $state['versions'] ?? null;
        if (is_array($storedVersions)) {
            foreach ($storedVersions as $stored) {
                $document = $this->validDocument($stored, false);
                if ($document !== null) {
                    $versions[(int)$document['version']] = $document;
                }
            }
        }
        if ($versions === []) {
            $default = $this->defaultDocument();
            $versions[1] = $default;
        }
        ksort($versions, SORT_NUMERIC);

        return $versions;
    }

    /** @param array<string,mixed> $state @param array<int,array<string,mixed>> $versions @return array<string,mixed> */
    private function approvedFrom(array $state, array $versions): array {
        $approvedVersion = filter_var($state['approvedVersion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($approvedVersion !== false && isset($versions[$approvedVersion])) {
            return $versions[$approvedVersion];
        }

        return $versions[max(array_keys($versions))];
    }

    /** @param array<string,mixed> $input @return array<string,mixed> */
    private function validateInput(array $input, int $version): array {
        foreach (self::FIELDS as $field) {
            if (!array_key_exists($field, $input) || !is_string($input[$field])) {
                throw new DomainException('Alle Abrechnungsstammdaten müssen als Text übergeben werden.');
            }
        }
        if (array_diff(array_keys($input), self::FIELDS) !== []) {
            throw new DomainException('Die Dokumentkonfiguration enthält unbekannte Felder.');
        }

        $document = ['version' => $version, 'outputLocale' => self::OUTPUT_LOCALE, 'headerMode' => 'text'];
        foreach (self::FIELDS as $field) {
            $document[$field] = $this->normalizeText($field, $input[$field]);
        }
        foreach (['introText', 'footerText'] as $field) {
            $this->validatePlaceholders($document[$field]);
        }

        return $document;
    }

    /** @return array<string,mixed>|null */
    private function validDocument(mixed $stored, bool $allowDraft): ?array {
        if (!is_array($stored)) {
            return null;
        }
        $version = filter_var($stored['version'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if ($version === false || ($stored['outputLocale'] ?? null) !== self::OUTPUT_LOCALE) {
            return null;
        }
        $input = [];
        foreach (self::FIELDS as $field) {
            if (!isset($stored[$field]) || !is_string($stored[$field])) {
                return null;
            }
            $input[$field] = $stored[$field];
        }
        try {
            $document = $this->validateInput($input, (int)$version);
            if (($stored['headerMode'] ?? null) === 'legacy-image' && (int)$version === 1) {
                $document['headerMode'] = 'legacy-image';
            }
            return $document;
        } catch (DomainException) {
            return null;
        }
    }

    private function normalizeText(string $field, string $value): string {
        if (preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $value) === 1) {
            throw new DomainException('Steuerzeichen sind in Dokumenttexten nicht zulässig.');
        }
        $value = str_replace(["\r\n", "\r"], "\n", trim($value));
        $limits = [
            'organizationName' => 160,
            'organizationAddress' => 500,
            'issuePlace' => 120,
            'introText' => 800,
            'footerText' => 800,
        ];
        if (strlen($value) > $limits[$field]) {
            throw new DomainException('Ein Dokumenttext überschreitet die zulässige Länge.');
        }
        if (in_array($field, ['organizationName', 'organizationAddress', 'issuePlace'], true) && $value === '') {
            throw new DomainException('Organisationsname, Anschrift und Ausstellungsort dürfen nicht leer sein.');
        }
        if (in_array($field, ['organizationName', 'issuePlace'], true) && str_contains($value, "\n")) {
            throw new DomainException('Organisationsname und Ausstellungsort müssen einzeilig sein.');
        }
        $lines = explode("\n", $value);
        $lineLimit = $field === 'organizationAddress' ? 4 : 3;
        if (count($lines) > $lineLimit) {
            throw new DomainException('Anschrift und optionale Dokumenttexte dürfen nur wenige, klar lesbare Zeilen enthalten.');
        }
        foreach ($lines as $line) {
            if (strlen($line) > 120) {
                throw new DomainException('Eine Dokumentzeile ist zu lang für das feste Abrechnungsformular.');
            }
        }

        return $value;
    }

    private function validatePlaceholders(string $text): void {
        if (preg_match_all('/\{\{([a-z_]+)\}\}/', $text, $matches) === false) {
            throw new DomainException('Die Platzhalter konnten nicht validiert werden.');
        }
        foreach ($matches[1] as $placeholder) {
            if (!in_array($placeholder, self::PLACEHOLDERS, true)) {
                throw new DomainException('Der Platzhalter {{' . $placeholder . '}} ist nicht zulässig.');
            }
        }
        $withoutValid = (string)preg_replace('/\{\{[a-z_]+\}\}/', '', $text);
        if (str_contains($withoutValid, '{{') || str_contains($withoutValid, '}}')) {
            throw new DomainException('Platzhalter müssen exakt im Format {{name}} angegeben werden.');
        }
    }

    /** @param array<string,mixed> $state */
    private function persist(array $state): void {
        $this->config->setValueString(
            Application::APP_ID,
            self::CONFIG_KEY,
            json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
        );
    }

    /** @return array<string,mixed> */
    private function defaultState(): array {
        return [
            'schemaVersion' => 1,
            'approvedVersion' => 1,
            'versions' => [1 => $this->defaultDocument()],
            'draft' => null,
        ];
    }

    /** @return array<string,mixed> */
    private function defaultDocument(): array {
        return self::bundledDocument();
    }

    /** @return array<string,mixed> */
    private static function bundledDocument(): array {
        return [
            'version' => 1,
            'outputLocale' => self::OUTPUT_LOCALE,
            'headerMode' => 'legacy-image',
            'organizationName' => 'ambulante dienste e.V.',
            'organizationAddress' => "Wilhelm-Kabus-Str. 21-35\n10829 Berlin-Schöneberg",
            'issuePlace' => 'Berlin',
            'introText' => '',
            'footerText' => '',
        ];
    }
}
