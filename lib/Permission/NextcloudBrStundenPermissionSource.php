<?php

declare(strict_types=1);

namespace OCA\BrStunden\Permission;

use OCA\BrStunden\Service\BrGroupsService;
use OCA\BrStunden\Service\CapabilitySettingsService;

final class NextcloudBrStundenPermissionSource implements BrStundenPermissionSourceInterface {
    public function __construct(private BrGroupsService $groups, private CapabilitySettingsService $settings) {}

    public function status(): array {
        $snapshot = $this->groups->snapshot();
        if ($snapshot['status'] !== 'VALID') return ['complete' => false, 'warning' => $snapshot['warning']];
        if (!$this->settings->isComplete()) {
            return ['complete' => false, 'warning' => 'Mindestens eine gespeicherte BR-Stunden-Berechtigungszuordnung ist ungültig.'];
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
