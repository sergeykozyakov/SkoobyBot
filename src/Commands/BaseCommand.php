<?php
namespace SkoobyBot\Commands;

use Telegram\Bot\Exceptions\TelegramSDKException;

class BaseCommand
{
    protected $logger = null;
    protected $api = null;
    protected $isCron = false;

    protected $message = null;
    protected $chatId = null;

    protected $replyMarkup = null;

    public function __construct($api, $logger) {
        if (!$logger) {
            throw new \Exception('Logger component is not defined!');
        }

        $this->logger = $logger;

        if (!$api) {
            throw new \Exception('Telegram API component is not defined!');
        }

        $this->api = $api;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function getApi() {
        return $this->api;
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
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = 'Я получил твоё сообщение! Если нужна помощь, то набери /help.';
        $this->sendMessage($response);
    }

    protected function sendMessage($text, $parseMode = null, $disablePreview = null) {
        if (!$this->getChatId()) {
            throw new \Exception('Telegram API chat id is not defined!');
        }

        try {
            $this->getApi()->sendMessage([
                'chat_id' => $this->getChatId(),
                'text' => $text,
                'reply_markup' => $this->getReplyMarkup(),
                'parse_mode' => $parseMode,
                'disable_web_page_preview' => $disablePreview
            ]);
        } catch (TelegramSDKException $e) {
            throw new \Exception('Cannot send message via Telegram API! (' . $e->getMessage() . ')');
        }
    }

    protected function sendPhoto($photo, $caption = null) {
        if (!$this->getChatId()) {
            throw new \Exception('Telegram API chat id is not defined!');
        }

        try {
            $this->getApi()->sendPhoto([
                'chat_id' => $this->getChatId(),
                'photo' => $photo,
                'caption' => $caption
            ]);
        } catch (TelegramSDKException $e) {
            throw new \Exception('Cannot send photo via Telegram API! (' . $e->getMessage() . ')');
        }
    }
}
