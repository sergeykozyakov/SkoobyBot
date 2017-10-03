<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class DelVkCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = 'Ты действительно хочешь отключить импорт постов из VK? Если да, то пришли что-нибудь.';
        $responseMain = "Импорт постов из VK отключен. Надеюсь, потом ты передумаешь \xF0\x9F\x98\x89";

        try {
            $state = $this->getBotState();
            $text = $this->getMessage()->getText();

            if ($state == 'default') {
                $this->sendMessage($response);
                $this->getDatabase()->setBotState($this->getChatId(), 'del_vk_main');
            }
            else if ($state == 'del_vk_main') {
                $this->sendMessage($responseMain);

                $this->getDatabase()->setVkWall($this->getChatId(), '');
                $this->getDatabase()->setBotState($this->getChatId(), 'default');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
