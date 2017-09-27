<?php
namespace SkoobyBot;

include('vendor/autoload.php');

require_once 'Config.php';
require_once 'Listener.php';

use SkoobyBot\Listener;

$listener = new Listener();
$listener->getUpdates();