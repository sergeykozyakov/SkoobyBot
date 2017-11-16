<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;
use SkoobyBot\Actions\Listener;

class DelVkCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        try {
            $response = $this->getLanguage()->get('del_vk_command');
            $responseMain = $this->getLanguage()->get('del_vk_command_main', [
                'smile' => "\xF0\x9F\x98\x89"
            ]);
    
            $responseNoConnect = $this->getLanguage()->get('del_vk_command_no_connect', [
                'smile' => "\xF0\x9F\x98\x89"
            ]);

            $state = $this->getBotState();
            $text = $this->getMessage()->getText();

            $user = $this->getDatabase()->getUser($this->getChatId());
            $isConnected = isset($user['vk_wall']) && $user['vk_wall'] && isset($user['channel']) && $user['channel'];

            if (!$isConnected) {
                $keyboard = Listener::getDefaultKeyboard(false, $this->getLanguage());
                array_splice($keyboard, -1);

                $this->setReplyMarkup(
                    $this->getApi()->replyKeyboardMarkup([
                        'keyboard' => $keyboard,
                        'resize_keyboard' => true,
                        'one_time_keyboard' => false
                    ])
                );

                $this->sendMessage($responseNoConnect);
                $this->getDatabase()->setBotState($this->getChatId(), 'default');
                return;
            }

            if ($state == 'del_vk_main') {
                $this->sendMessage($responseMain);

                $this->getDatabase()->delVkConnection($this->getChatId());
                $this->getDatabase()->setBotState($this->getChatId(), 'default');
            }
            else {
                $this->sendMessage($response);
                $this->getDatabase()->setBotState($this->getChatId(), 'del_vk_main');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
