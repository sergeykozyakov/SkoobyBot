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

        try {
            $this->sendMessage($response);

            $this->getDatabase()->addUser($this->getChatId());
            $this->getDatabase()->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
