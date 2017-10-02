<?php
namespace SkoobyBot;

use SkoobyBot\Config;
use SkoobyBot\Database;

use SkoobyBot\Actions\Sender;
use SkoobyBot\Actions\Listener;

use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class App
{
    private $logger = null;
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

    public function start($mode = '') {
        $logDir = Config::getLogDir();

        try {
            $logger = new Logger(__DIR__ . '/' . $logDir, LogLevel::WARNING);
            $this->logger = $logger;
        } catch (\Exception $e) {
            echo '[ERROR] Logger system problems occured! ' . $e->getMessage();
            return;
        }

        try {
            $db = Database::getInstance();
            $db->init();
        } catch (\Exception $e) {
            $this->getLogger()->error($e->getMessage());

            echo "Database connection problems occured:\n" . $e->getMessage();
            return;
        }

        if (isset($_GET['cron']) || $mode == 'cron') {
            try {
                $sender = new Sender($this->getLogger());
                $sender->start();
            } catch (\Exception $e) {
                echo "Telegram API Sender problems occured:\n" . $e->getMessage();
            }
        }
        else {
            try {
                $listener = new Listener($this->getLogger());
                $listener->start();
            } catch (\Exception $e) {
                echo "Telegram API Listener problems occured:\n" . $e->getMessage();
            }
        }
    }
 
    private function __clone() {}
    private function __construct() {
        Config::init();
    }
}
