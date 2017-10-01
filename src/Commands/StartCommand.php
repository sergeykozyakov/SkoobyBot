<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class StartCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $firstName = $this->getMessage()->getChat()->getFirstName();
        $response = 'Привет, ' . $firstName . '! Я Skooby Bot. Как дела?';

        $this->sendMessage($response);

        try {
            $this->getUser()->addUser($this->getChatId());
        } catch (\Exception $e) {
            throw new \Exception('Cannot add user to database (' . $e->getMessage() . ')');
        }
    }
}
