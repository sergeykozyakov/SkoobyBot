<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class HelpCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = $this->getLanguage()->get('help_command');

        try {
            $this->sendMessage($response);
            $this->getDatabase()->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
