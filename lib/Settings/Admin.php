<?php

declare(strict_types=1);

namespace OCA\BrStunden\Settings;

use OCA\BrStunden\AppInfo\Application;
use OCA\BrStunden\Service\BrGroupsService;
use OCA\BrStunden\Service\CapabilitySettingsService;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Settings\ISettings;

final class Admin implements ISettings {
    public function __construct(private CapabilitySettingsService $settings, private BrGroupsService $groups) {}
    public function getForm(): TemplateResponse {
        return new TemplateResponse(Application::APP_ID, 'admin', [
            'assignments' => $this->settings->current(),
            'roleSource' => $this->groups->snapshot(),
        ]);
    }
    public function getSection(): string { return Application::APP_ID; }
    public function getPriority(): int { return 30; }
}
