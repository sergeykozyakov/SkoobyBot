<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class HelpCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            $this->getLogger()->error('Telegram API message is not defined!');
            throw new \Exception('[ERROR] Telegram API message is not defined!');
        }

        $response = 'Смотри, основные команды — это /start и /help и пока этого достаточно. ' .
            'В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет. ' .
            'Ещё недавно появился запрос последнего поста из VK — это /getPost.';

        $this->sendMessage($response);
    }
}
