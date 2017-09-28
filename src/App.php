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

    private function __clone() {}
    private function __construct() {}

    protected function getLogger() {
        return $this->logger;
    }

    protected function setLogger($logger) {
        $this->logger = $logger;
        return $this;
    }

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function start() {
        $logDir = Config::getLogDir();
        if (!$logDir) {
            echo '[ERROR] SkoobyBot Logger path not found!';
            return;
        }

        try {
            $logger = new Logger(__DIR__ . '/' . $logDir, LogLevel::DEBUG);
            $this->setLogger($logger);
        } catch (\Exception $e) {
            echo '[ERROR] SkoobyBot Logger system does not work! ' . $e->getMessage();
            return;
        }

        try {
            $listener = new Listener($this->getLogger());
            $listener->getUpdates();
        } catch (\Exception $e) {
            echo $e->getMessage();
        }
    }
}
