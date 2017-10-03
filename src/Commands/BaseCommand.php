<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Database;

use Telegram\Bot\Exceptions\TelegramSDKException;

class BaseCommand
{
    private $logger = null;
    private $api = null;
    private $database = null;

    private $isCron = false;

    private $message = null;
    private $chatId = null;

    private $replyMarkup = null;

    public function __construct($api, $logger) {
        if (!$logger) {
            throw new \Exception('Logger component is not defined!');
        }

        $this->logger = $logger;

        if (!$api) {
            throw new \Exception('Telegram API component is not defined!');
        }

        $this->api = $api;

        try {
            $this->database = Database::getInstance();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getLogger() {
        return $this->logger;
    }

    public function getApi() {
        return $this->api;
    }

    public function getDatabase() {
        return $this->database;
    }

    public function setIsCron($isCron) {
        $this->isCron = $isCron;
        return $this;
    }

    public function getIsCron() {
        return $this->isCron;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        if (!$message) {
            throw new \Exception('Telegram API message is null!');
        }

        $this->message = $message;
        $this->chatId = $message->getChat()->getId();
        return $this;
    }

    public function getChatId() {
        return $this->chatId;
    }

    public function setReplyMarkup($replyMarkup) {
        $this->replyMarkup = $replyMarkup;
        return $this;
    }

    public function getReplyMarkup() {
        return $this->replyMarkup;
    }

    public function start() {
        if (!$this->message) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = 'Я получил твоё сообщение! Если нужна помощь, то набери /help.';

        try {
            $this->sendMessage($response);
            $this->database->setBotState($this->chatId, '');
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function sendMessage($text, $parseMode = null, $disablePreview = null) {
        if (!$this->chatId) {
            throw new \Exception('Telegram API chat_id is not defined!');
        }

        try {
            $this->api->sendMessage([
                'chat_id' => $this->chatId,
                'text' => $text,
                'reply_markup' => $this->replyMarkup,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => $disablePreview
            ]);
        } catch (TelegramSDKException $e) {
            throw new \Exception('Cannot send message via Telegram API! (' . $e->getMessage() . ')');
        }
    }

    protected function sendPhoto($photo, $caption = null) {
        if (!$this->chatId) {
            throw new \Exception('Telegram API chat_id is not defined!');
        }

        try {
            $this->api->sendPhoto([
                'chat_id' => $this->chatId,
                'photo' => $photo,
                'caption' => $caption
            ]);
        } catch (TelegramSDKException $e) {
            throw new \Exception('Cannot send photo via Telegram API! (' . $e->getMessage() . ')');
        }
    }
}
