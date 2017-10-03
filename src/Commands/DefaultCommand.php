<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class DefaultCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = 'Я получил твоё сообщение! Если нужна помощь, то набери /help.' + $this->getChatId();

        try {
            $this->sendMessage($response);
            $this->database->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
