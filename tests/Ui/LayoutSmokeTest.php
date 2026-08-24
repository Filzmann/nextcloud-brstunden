<?php

declare(strict_types=1);

$css = file_get_contents(__DIR__ . '/../../css/style.css');
if ($css === false) {
    throw new RuntimeException('BRStunden-Stylesheet konnte nicht gelesen werden.');
}
if (preg_match('/#brstunden-app\s*\{[^}]*box-sizing:\s*border-box[^}]*width:\s*100%[^}]*max-width:\s*none[^}]*height:\s*100%[^}]*min-height:\s*0[^}]*overflow-y:\s*auto[^}]*background:\s*var\(--color-main-background\)/s', $css) !== 1) {
    throw new RuntimeException('BRStunden-App-Root erfüllt den Vollbreiten- und Scrollvertrag nicht.');
}

echo "LayoutSmokeTest: OK\n";
