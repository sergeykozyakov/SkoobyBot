<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Database;
use SkoobyBot\Languages\Language;

use Telegram\Bot\Exceptions\TelegramSDKException;

abstract class BaseCommand
{
    private $logger = null;
    private $api = null;
    private $database = null;
    private $language = null;

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
        $this->language = Language::getInstance();

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

    public function getLanguage() {
        return $this->language;
    }

    public function getIsCron() {
        return $this->isCron;
    }

    public function setIsCron($isCron) {
        $this->isCron = $isCron;
        return $this;
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

    protected function setChatId($chatId) {
        $this->chatId = $chatId;
        return $this;
    }

    public function getReplyMarkup() {
        return $this->replyMarkup;
    }

    public function setReplyMarkup($replyMarkup) {
        $this->replyMarkup = $replyMarkup;
        return $this;
    }

    protected function getBotState() {
        if (!$this->chatId) {
            throw new \Exception('Telegram API chat_id is not defined!');
        }

        $botState = '';
        try {
            $user = $this->database->getUser($this->chatId);
            $botState = (isset($user['bot_state']) && $user['bot_state']) ? $user['bot_state'] : 'default';
        } catch (\Exception $e) {
            throw new \Exception('Cannot get user bot_state! (' . $e->getMessage() . ')');
        }

        return $botState;
    }

    protected function sendMessage($text, $parseMode = null, $disablePreview = null) {
        if (!$this->chatId) {
            throw new \Exception('Telegram API chat_id is not defined!');
        }

        $message = null;

        try {
            $message = $this->api->sendMessage([
                'chat_id' => $this->chatId,
                'text' => $text,
                'reply_markup' => $this->replyMarkup,
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => $disablePreview
            ]);
        } catch (TelegramSDKException $e) {
            throw new \Exception('Cannot send message via Telegram API! (' . $e->getMessage() . ')');
        }

        if (!$this->isCron) return;

        try {
            $this->database->addPost($this->chatId, $message->getMessageId());
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function sendPhoto($photo, $caption = null) {
        if (!$this->chatId) {
            throw new \Exception('Telegram API chat_id is not defined!');
        }

        $message = null;

        try {
            $message = $this->api->sendPhoto([
                'chat_id' => $this->chatId,
                'photo' => $photo,
                'caption' => $caption,
                'reply_markup' => $this->replyMarkup
            ]);
        } catch (TelegramSDKException $e) {
            throw new \Exception('Cannot send photo via Telegram API! (' . $e->getMessage() . ')');
        }

        if (!$this->isCron) return;

        try {
            $this->database->addPost($this->chatId, $message->getMessageId());
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
