<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

use OCA\BrStunden\Service\BrGroupsService;
use OCA\BrStunden\Service\CapabilitySettingsService;
use OCP\IL10N;

final class NextcloudBrStundenPermissionSource implements BrStundenPermissionSourceInterface {
    public function __construct(private BrGroupsService $groups, private CapabilitySettingsService $settings, private ?IL10N $l10n = null) {}

    /** Lokalisierung fachlicher Texte; im Test ohne IL10N identity. */
    private function t(string $text): string
    {
        return $this->l10n?->t($text) ?? $text;
    }

    public function status(): array {
        $snapshot = $this->groups->snapshot();
        if ($snapshot['status'] !== 'VALID') return ['complete' => false, 'warning' => $snapshot['warning']];
        if (!$this->settings->isComplete()) {
            return ['complete' => false, 'warning' => $this->t('Mindestens eine gespeicherte BR-Stunden-Berechtigungszuordnung ist ungültig.')];
        }
        return ['complete' => true, 'warning' => null];
    }

    public function groupIds(): array {
        $snapshot = $this->groups->snapshot();
        return $snapshot['status'] === 'VALID' ? $snapshot['groups'] : [];
    }

    public function assignments(): array {
        return $this->settings->current();
    }
}
