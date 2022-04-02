<?php declare(strict_types=1);

use App\Settings\SettingsModel;
use App\AppMain;

$settings = new SettingsModel();
$auth = (new AppMain())->getAuth();
if (!$auth->isAuthenticated() && isset($_COOKIE['theme_dark_mode'])) {
    $use_theme_dark_mode = $_COOKIE['theme_dark_mode'];
} else {
    $profile = $settings->getUserSetting($auth->getAuthId(), 'profile');
    $use_theme_dark_mode = $profile['theme_dark_mode'] ?? null;
}

if (!isset($title)) {
    $title = 'PPM';
}

if (!isset($description)) {
    $description = $title;
}

require 'templates/helpers.php';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title><?=$title?></title>
    <meta name="description" content="<?=$description?>">
    <meta name="theme-color" content="#ffffff">
    
    <?php

    if ($use_theme_dark_mode): ?>
    <link rel="stylesheet" id="js-startup-stylesheet" href="/css/water/dark.min.css">
    <?php else: ?>
    <link rel="stylesheet" id="js-startup-stylesheet" href="/css/water/light.min.css">
    <?php endif; ?>

    <link rel="manifest" href="/assets/manifest.json">
    <link rel="stylesheet" href="/css/default.css?v=0.9">
    <link rel="icon" sizes="192x192" href="/assets/ppm-logo-192x192.png">
    <script type="module">
    	import {GlobalEvents} from '/js/global_events.js';
    </script>
</head>
<body>
    <div class="page">

<?php

require 'templates/main_menu.tpl.php';

require 'templates/flash.tpl.php';
