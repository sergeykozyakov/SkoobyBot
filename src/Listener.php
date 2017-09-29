<?php
namespace SkoobyBot;

use SkoobyBot\Config;
use SkoobyBot\Commands\StartCommand;
use SkoobyBot\Commands\HelpCommand;
use SkoobyBot\Commands\GetVkCommand;
use SkoobyBot\Commands\BaseCommand;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class Listener
{
    protected $logger = null;
    protected $api = null;

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

    public function getLogger() {
        return $this->logger;
    }

    public function getApi() {
        return $this->api;
    }

    public function getUpdates() {
        if (!$this->getApi()) {
            $this->getLogger()->error('Cannot receive message until Telegram API is connected!');
            throw new \Exception('[ERROR] Cannot receive message until Telegram API is connected!');
        }

        $result = $this->getApi()->getWebhookUpdates();

        if (!$result->getMessage()) {
            $this->getLogger()->error('Cannot read received Telegram API message!');
            throw new \Exception('[ERROR] Cannot read received Telegram API message!');
        }

        $text = $result->getMessage()->getText();

        switch ($text) {
            case '/start':
                $keyboard = [["\xE2\x9E\xA1 Помощь"], ["\xE2\x9E\xA1 Последний пост VK"]];
                $replyMarkup = $this->getApi()->replyKeyboardMarkup([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false
                ]);

                try {
                    $startCommand = new StartCommand($this->getApi(), $this->getLogger());
                    $startCommand
                        ->setMessage($result->getMessage())
                        ->setReplyMarkup($replyMarkup)
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error('Cannot execute bot /start command: ' . $e->getMessage());
                    throw new \Exception('[ERROR] Cannot execute bot /start command: ' . $e->getMessage());
                }
                break;
            case '/help':
            case "\xE2\x9E\xA1 Помощь":
                try {
                    $helpCommand = new HelpCommand($this->getApi(), $this->getLogger());
                    $helpCommand
                        ->setMessage($result->getMessage())
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error('Cannot execute bot /help command: ' . $e->getMessage());
                    throw new \Exception('[ERROR] Cannot execute bot /help command: ' . $e->getMessage());
                }
                break;
            case '/getVk':
            case "\xE2\x9E\xA1 Последний пост VK":
                try {
                    $getVkCommand = new GetVkCommand($this->getApi(), $this->getLogger());
                    $getVkCommand
                        ->setMessage($result->getMessage())
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error('Cannot execute bot /getVk command: ' . $e->getMessage());
                    throw new \Exception('[ERROR] Cannot execute bot /getVk command: ' . $e->getMessage());
                }
                break;
            default:
                try {
                    $defaultCommand = new BaseCommand($this->getApi(), $this->getLogger());
                    $defaultCommand
                        ->setMessage($result->getMessage())
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error('Cannot execute bot default command: ' . $e->getMessage());
                    throw new \Exception('[ERROR] Cannot execute bot default command: ' . $e->getMessage());
                }
                break;
        }
    }
}
