<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Config;
use SkoobyBot\Database;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

abstract class BaseAction
{
    private $logger = null;
    private $api = null;
    private $database = null;

    public function __construct($logger) {
        if (!$logger) {
            throw new \Exception('[ERROR] Logger component is not defined!');
        }

        $this->logger = $logger;
        $token = Config::getTelegramToken();

        if (!$token) {
            $this->logger->error('No Telegram API token was specified!');
            throw new \Exception('[ERROR] No Telegram API token was specified!');
        }

        try {
            $api = new Api($token);
            $this->api = $api;
        } catch (TelegramSDKException $e) {
            $this->logger->error('Telegram API connection error! ' . $e->getMessage());
            throw new \Exception('[ERROR] Telegram API connection error!');
        }

        try {
            $this->database = Database::getInstance();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    abstract public function start();

    public function getLogger() {
        return $this->logger;
    }

    public function getApi() {
        return $this->api;
    }

    public function getDatabase() {
        return $this->database;
    }
}
