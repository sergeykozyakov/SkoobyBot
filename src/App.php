<?php
namespace SkoobyBot;

use SkoobyBot\Config;
use SkoobyBot\Listener;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class App
{
    protected $logger = null;
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function start() {
        $logDir = Config::getLogDir();

        try {
            $logger = new Logger(__DIR__ . '/' . $logDir, LogLevel::WARNING);
            $this->logger = $logger;
        } catch (\Exception $e) {
            echo '[ERROR] Logger system problems occured! ' . $e->getMessage();
            return;
        }

        try {
            $listener = new Listener($this->getLogger());
            $listener->getUpdates();
        } catch (\Exception $e) {
            echo "Telegram API Listener problems occured:\n" . $e->getMessage();
        }
    }
 
    private function __clone() {}
    private function __construct() {
        Config::init();
    }
}
