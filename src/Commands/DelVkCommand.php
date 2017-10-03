<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class DelVkCommand extends BaseCommand
{
    public function start() {
        /*if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $firstName = $this->getMessage()->getChat()->getFirstName();
        $response = 'Привет, ' . $firstName . '! Я Skooby Bot. Как дела?';

        try {
            $this->sendMessage($response);

            $this->getDatabase()->setBotState($this->getChatId(), 'del_vk_main');
            $this->getDatabase()->addUser($this->getChatId());
        } catch (\Exception $e) {
            throw $e;
        }*/
    }
}
