<?php
namespace SkoobyBot;

include('vendor/autoload.php');

require_once 'Config.php';
require_once 'Listener.php';

use SkoobyBot\Listener;

class App
{
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __clone() {}
    private function __construct() {}
    
    public function start() {
        $listener = new Listener();

        if ($listener->getApi()) {
            $listener->getUpdates();
        }
    }
}