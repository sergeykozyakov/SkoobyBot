<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;
use SkoobyBot\Actions\Listener;

class SetVkCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $firstName = $this->getMessage()->getChat()->getFirstName();

        try {
            $response = $this->getLanguage()->get('set_vk_command', array(
                'name' => $firstName
            ));

            $responseConnected = $this->getLanguage()->get('set_vk_command_connected');
            $responseMain = $this->getLanguage()->get('set_vk_command_main');
            $responseMainConnected = $this->getLanguage()->get('set_vk_command_main_connected');
            $responseTelegram = $this->getLanguage()->get('set_vk_command_telegram');
            $responseTelegramVerify = $this->getLanguage()->get('set_vk_command_telegram_verify');

            $responseFailed = $this->getLanguage()->get('set_vk_command_failed', array(
                'smile' => "\xF0\x9F\x98\xB5"
            ));
            $responseTelegramFailed = $this->getLanguage()->get('set_vk_command_telegram_failed');
            $responseTelegramVerifyFailed = $this->getLanguage()->get('set_vk_command_telegram_verify_failed');

            $state = $this->getBotState();
            $text = $this->getMessage()->getText();

            $user = $this->getDatabase()->getUser($this->getChatId());
            $isConnected = isset($user['vk_wall']) && $user['vk_wall'] && isset($user['channel']) && $user['channel'];

            if ($state == 'set_vk_main') {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $text)) {
                    $this->sendMessage($responseFailed);
                    return;
                }

                $this->sendMessage($isConnected ? $responseMainConnected : $responseMain);

                $this->getDatabase()->setVkWall($this->getChatId(), $text);
                $this->getDatabase()->setBotState($this->getChatId(), 'set_vk_telegram');
            }
            else if ($state == 'set_vk_telegram') {
                $keyboard = Listener::getDefaultKeyboard(false, $this->getLanguage());
                array_splice($keyboard, 0, -1);

                $replyMarkup = $this->getApi()->replyKeyboardMarkup([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false
                ]);

                if (!preg_match('/^[@][a-zA-Z0-9_]+$/', $text)) {
                    $this->setReplyMarkup($replyMarkup);
                    $this->sendMessage($responseFailed);
                    return;
                }

                $row = $this->getDatabase()->getIsChannelConnected($this->getChatId(), $text);
                $isChannelConnected = isset($row['count']) && $row['count'] > 0;

                if ($isChannelConnected) {
                    $this->setReplyMarkup($replyMarkup);
                    $this->sendMessage($responseTelegramFailed);
                    return;
                }

                $originalChatId = $this->getChatId();
                $originalReplyMarkup = $this->getReplyMarkup();

                $this->setChatId($text);
                $this->setReplyMarkup(null);

                try {
                    $this->sendMessage($responseTelegramVerify);
                } catch (\Exception $e) {
                    $this->setChatId($originalChatId);
                    $this->setReplyMarkup($replyMarkup);
                    $this->sendMessage($responseTelegramVerifyFailed);
                    return;
                }

                $this->setChatId($originalChatId);
                $this->setReplyMarkup($originalReplyMarkup);
                $this->sendMessage($responseTelegram);

                $this->getDatabase()->setChannel($this->getChatId(), $text);
                $this->getDatabase()->setBotState($this->getChatId(), 'default');
            }
            else {
                $this->sendMessage($isConnected ? $responseConnected : $response, null, true);
                $this->getDatabase()->setBotState($this->getChatId(), 'set_vk_main');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
