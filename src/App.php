<?php
namespace SkoobyBot;

include('vendor/autoload.php');

require_once 'Config.php';
require_once 'Listener.php';

use SkoobyBot\Config;
use SkoobyBot\Listener;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class App
{
    protected $logger = null;
    private static $instance = null;

    private function __clone() {}

    private function __construct() {
        try {
            $logDir = Config::getLogDir();
            if (!$logDir) {
                echo 'SkoobyBot Logger path not found!';
                return;
            }

            $logger = new Logger(__DIR__ . '/' . $logDir, LogLevel::WARNING);
            $this->setLogger($logger);
        } catch (\Exception $e) {
            echo 'SkoobyBot Logger system does not work!';
        }
    }

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
        if (!$this->getLogger()) return;

        $listener = new Listener($this->getLogger());

        if ($listener->getApi()) {
            $listener->getUpdates();
        }
    }
}