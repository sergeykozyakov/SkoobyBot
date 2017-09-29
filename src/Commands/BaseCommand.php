<?php
namespace SkoobyBot\Commands;

use Telegram\Bot\Exceptions\TelegramSDKException;

class BaseCommand
{
    protected $logger = null;
    protected $api = null;

    protected $message = null;
    protected $chatId = null;

    protected $replyMarkup = null;

    public function __construct($api, $logger) {
        if (!$logger) {
            throw new \Exception('[ERROR] Logger component is not defined!');
        }

        $this->logger = $logger;

        if (!$api) {
            $this->getLogger()->error('Telegram API component is not defined!');
            throw new \Exception('[ERROR] Telegram API component is not defined!');
        }

        $this->api = $api;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function getApi() {
        return $this->api;
    }

    public function getMessage() {
        return $this->message;
    }

    public function setMessage($message) {
        if (!$message) {
            $this->getLogger()->error('Telegram API message is null!');
            throw new \Exception('[ERROR] Telegram API message is null!');
        }

        $this->message = $message;
        $this->chatId = $message->getChat()->getId();
        return $this;
    }

    public function setReplyMarkup($replyMarkup) {
        $this->replyMarkup = $replyMarkup;
        return $this;
    }

    public function getReplyMarkup() {
        return $this->replyMarkup;
    }

    public function getChatId() {
        return $this->chatId;
    }

    public function start() {
        if (!$this->getMessage()) {
            $this->getLogger()->error('Telegram API message is not defined!');
            throw new \Exception('[ERROR] Telegram API message is not defined!');
        }

        $response = 'Я получил твоё сообщение! Если нужна помощь, то набери /help.';
        $this->sendMessage($response);
    }

    protected function sendMessage($text, $parseMode = null, $disablePreview = null) {
        if (!$this->getChatId()) {
            $this->getLogger()->error('Telegram API chat id is not defined!');
            throw new \Exception('[ERROR] Telegram API chat id is not defined!');
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
            $this->getLogger()->error('Cannot send message via Telegram API! ' . $e->getMessage());
            throw new \Exception('[ERROR] Cannot send message via Telegram API!');
        }
    }

    protected function sendPhoto($photo, $caption = null) {
        if (!$this->getChatId()) {
            $this->getLogger()->error('Telegram API chat id is not defined!');
            throw new \Exception('[ERROR] Telegram API chat id is not defined!');
        }

        try {
            $this->getApi()->sendPhoto([
                'chat_id' => $this->getChatId(),
                'photo' => $photo,
                'caption' => $caption
            ]);
        } catch (TelegramSDKException $e) {
            $this->getLogger()->error('Cannot send photo via Telegram API! ' . $e->getMessage());
            throw new \Exception('[ERROR] Cannot send photo via Telegram API!');
        }
    }
}
