<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class CancelCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        try {
            $response = $this->getLanguage()->get('cancel_command');
            $this->sendMessage($response);
            $this->getDatabase()->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
