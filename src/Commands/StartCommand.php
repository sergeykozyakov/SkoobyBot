<?php

namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

use Telegram\Bot\Exceptions\TelegramSDKException;

class StartCommand extends BaseCommand
{
    protected $name = 'start';

    public function start() {
        if (!$this->getMessage()) {
            $this->getLogger()->error('Telegram API message is not defined!');
            throw new \Exception('[ERROR] Telegram API message is not defined!');
        }

        $firstName = $this->getMessage()->getChat()->getFirstName();
        $response = 'Привет, ' . $firstName . '! Я Skooby Bot. Как дела?';

        $this->sendMessage($response);
    }
}
