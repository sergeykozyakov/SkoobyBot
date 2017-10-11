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

        try {
            $response = $this->getLanguage()->get('start_command', array(
                'name' => $firstName
            ));

            $this->sendMessage($response);

            $this->getDatabase()->addUser($this->getChatId());
            $this->getDatabase()->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
