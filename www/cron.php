<?php
include('../vendor/autoload.php');

use SkoobyBot\App;

$app = App::getInstance();
$app->start('cron');
