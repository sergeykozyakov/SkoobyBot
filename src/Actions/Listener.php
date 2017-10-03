<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Actions\BaseAction;
use SkoobyBot\Commands\CommandFactory;

class Listener extends BaseAction
{
    public function start() {
        $result = $this->getApi()->getWebhookUpdates();

        if (!$result || !$result->getMessage()) {
            $this->getLogger()->error('Cannot read received Telegram API message!');
            throw new \Exception('[ERROR] Cannot read received Telegram API message!');
        }

        $text = $result->getMessage()->getText();
        $chatId = $result->getMessage()->getChat()->getId();

        $keyboard = [
            ["\xE2\x9E\xA1 Помощь"], ["\xE2\x9E\xA1 Последний пост VK"]
        ];

        $replyMarkup = $this->getApi()->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]);

        $botState = '';

        try {
            $user = $this->getDatabase()->getUser($chatId);
            $botState = (isset($user['bot_state']) && $user['bot_state']) ? $user['bot_state'] : 'default';
        } catch (\Exception $e) {
            $this->getLogger()->error('(chat_id: ' . $chatId . ') ' . $e->getMessage());
            throw new \Exception('[ERROR] ' . $e->getMessage());
        }

        $stateMap = $this->getStateMap();

        if (!isset($stateMap[$botState])) {
            $this->getLogger()->error(
                '(chat_id: ' . $chatId . ') State ' . $botState . ' not found!'
            );
            throw new \Exception('[ERROR] State ' . $botState . ' not found!');
        }

        $foundState = $stateMap[$botState];
        $action = $text;

        if (!isset($foundState[$action])) {
            if (!isset($foundState['/default'])) {
                $this->getLogger()->error(
                    '(chat_id: ' . $chatId . ') State ' . $botState . ' command ' . $action . ' not found!'
                );
                throw new \Exception('[ERROR] State ' . $botState . ' command ' . $action . ' not found!');
            }
            $action = '/default';
        }

        try {
            $command = CommandFactory::get($foundState[$action], $this->getApi(), $this->getLogger());
            $command
                ->setMessage($result->getMessage())
                ->setReplyMarkup($action == '/start' ? $replyMarkup : null)
                ->start();
        } catch (\Exception $e) {
            $this->getLogger()->error(
                '(chat_id: ' . $chatId . ') Cannot execute bot ' . $action . ' command: ' . $e->getMessage()
            );
            throw new \Exception('[ERROR] Cannot execute bot ' . $action . ' command: ' . $e->getMessage());
        }
    }

    private function getStateMap() {
        $defaultState = array(
            '/start' => 'StartCommand',
            '/help' => 'HelpCommand',
            "\xE2\x9E\xA1 Помощь" => 'HelpCommand',
            '/setVk' => 'SetVkCommand',
            "\xE2\x9E\xA1 Добавить привязку к VK" => 'SetVkCommand',
            '/getVk' => 'GetVkCommand',
            "\xE2\x9E\xA1 Последний пост VK" => 'GetVkCommand', 
            '/default' => 'DefaultCommand'
        );

        $setVkMainState = $defaultState;
        $setVkMainState['/default'] = 'SetVkCommand';

        $stateMap = array(
            'default' => $defaultState,
            'set_vk_login' => $setVkMainState
        );

        return $stateMap;
    }
}
