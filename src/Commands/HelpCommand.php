<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class HelpCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $userName = $this->getMessage()->getChat()->getUsername();
        $chatId = $this->getChatId();

        $response = 'Твой код чата = ' . $chatId . ', твой код пользователя = ' . $userName . '. ' .
            'В принципе, можно любой текст и картинку мне отправить. ' .
            'Также можно выполнить запрос последнего поста из VK — это /getVk.';

        $this->sendMessage($response);
    }
}
