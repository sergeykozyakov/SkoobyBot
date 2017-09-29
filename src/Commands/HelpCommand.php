<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class HelpCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = 'Смотри, основные команды — это /start и /help и пока этого достаточно. ' .
            'В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет. ' .
            'Ещё недавно появился запрос последнего поста из VK — это /getVk.';

        $this->sendMessage($response);
    }
}
