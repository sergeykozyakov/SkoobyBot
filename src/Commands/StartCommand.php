<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class StartCommand extends BaseCommand
{
    public function __construct($api, $logger) {
        parent::__construct($api, $logger);
    }

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
