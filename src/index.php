<?php
namespace SkoobyBot;

include('vendor/autoload.php');

require 'Config.php';
require 'Listener.php';

use SkoobyBot\Listener;

$listener = new Listener();
$listener->getUpdates();