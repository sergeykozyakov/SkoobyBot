<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Config;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class BaseAction
{
    private $logger = null;
    private $api = null;

    public function __construct($logger) {
        if (!$logger) {
            throw new \Exception('[ERROR] Logger component is not defined!');
        }

        $this->logger = $logger;
        $token = Config::getTelegramToken();

        if (!$token) {
            $this->getLogger()->error('No Telegram API token was specified!');
            throw new \Exception('[ERROR] No Telegram API token was specified!');
        }

        try {
            $api = new Api($token);
            $this->api = $api;
        } catch (TelegramSDKException $e) {
            $this->getLogger()->error('Telegram API connection error! ' . $e->getMessage());
            throw new \Exception('[ERROR] Telegram API connection error!');
        }
    }

    public function start() {}

    public function getLogger() {
        return $this->logger;
    }

    public function getApi() {
        return $this->api;
    }
}
