<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class DefaultCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        try {
            $response = $this->getLanguage()->get('default_command', [
                'smile' => "\xF0\x9F\x98\x8A"
            ]);

            $this->sendMessage($response);
            $this->getDatabase()->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
