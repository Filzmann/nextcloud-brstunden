<?php

declare(strict_types=1);

namespace OCA\BrStunden\Service;

use OCA\LocalBase\Organization\BrGroupDefinition;
use OCA\LocalBase\Organization\BrGroupSettingsService;
use OCA\LocalBase\Service\GroupProvisioningService;

class BrGroupsService {
    public function __construct(
        private GroupProvisioningService $groups,
        private BrGroupSettingsService $settings,
    ) {
    }

    public function requiredGroups(): array {
        $state = $this->settings->state();
        $definition = $state['persisted']
            ? $this->settings->validatedDefinition()
            : BrGroupDefinition::defaults();
        return array_values($definition->groups());
    }

    public function ensureRequiredGroups(): array {
        $state = $this->settings->state();
        if ($state['persisted']) {
            $this->settings->validatedDefinition();
            return [];
        }

        $created = $this->groups->ensureGroups($this->requiredGroups());
        $this->settings->initializeFromLegacyMemberGroup(
            BrGroupDefinition::defaults()->groupId(BrGroupDefinition::MEMBER),
        );
        return $created;
    }

    public function memberGroupName(): string {
        return $this->settings->validatedDefinition()->groupId(BrGroupDefinition::MEMBER);
    }
}
