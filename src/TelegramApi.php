<?php
namespace SkoobyBot;

use Telegram\Bot\Api;

class TelegramApi extends Api {
    public function editMessageReplyMarkup(array $params) {
        return $this->post('editMessageReplyMarkup', $params);
    }

    public function answerCallbackQuery(array $params) {
        return $this->post('answerCallbackQuery', $params);
    }
}