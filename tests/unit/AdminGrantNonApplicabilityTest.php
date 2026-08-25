<?php

declare(strict_types=1);

$root=dirname(__DIR__,2);$provider=(string)file_get_contents($root.'/lib/Permission/BrStundenPermissionProvider.php');$access=(string)file_get_contents($root.'/lib/Service/BrMemberService.php');$routes=(string)file_get_contents($root.'/appinfo/routes.php');
if(str_contains($provider,'nextcloudAdmin')||str_contains($provider,'temporaryAppAdminGrant'))throw new RuntimeException('BRStunden darf ohne fachlichen Adminpfad keine Adminfreigabe behaupten.');
if(str_contains($access,'isAdmin('))throw new RuntimeException('Ein neuer nativer Admin-Bypass erfordert eine erneute App-Freigabe-Bewertung.');
if(str_contains($routes,'admin/full-access'))throw new RuntimeException('Ohne fachlichen Adminpfad darf kein wirkungsloser Freigabeschalter existieren.');
echo "BRStunden admin grant non-applicability contract passed\n";
