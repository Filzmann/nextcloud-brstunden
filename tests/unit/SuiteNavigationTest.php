<?php

declare(strict_types=1);

$template = file_get_contents(__DIR__ . '/../../templates/index.php');
$info = file_get_contents(__DIR__ . '/../../appinfo/info.xml');
if ($template === false || $info === false) throw new RuntimeException('BRStunden-Vertragsdatei konnte nicht gelesen werden.');
if (!str_contains($info, '<app>orgsuite</app>') || !str_contains($info, '<app>localbase</app>') || str_contains($info, '<navigations>')) throw new RuntimeException('OrgSuite-/LocalBase-Appvertrag fehlt.');
if (!str_contains($info, '<version>0.4.4</version>')) throw new RuntimeException('Die BR-Gruppenmigration benötigt eine neue App-Version.');
foreach (["script('orgsuite', 'suite-navigation')", "style('orgsuite', 'suite-navigation')", 'data-orgsuite data-suite="br" data-current-app="brstunden"'] as $contract) {
    if (!str_contains($template, $contract)) throw new RuntimeException("Suite-Navigationsvertrag fehlt: {$contract}");
}
echo "BRStunden suite navigation test passed\n";
