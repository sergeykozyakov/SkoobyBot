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
            ["\xE2\x9E\x95 Добавить импорт из VK"], ["\xE2\x98\x95 Последний пост VK"],
            ["\xE2\x9D\x8C Удалить импорт из VK"], ["\xE2\x9D\x93 Помощь"]
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
            '/setVk' => 'SetVkCommand',
            "\xE2\x9E\x95 Добавить импорт из VK" => 'SetVkCommand',
            '/getVk' => 'GetVkCommand',
            "\xE2\x98\x95 Последний пост VK" => 'GetVkCommand', 
            '/delVk' => 'DelVkCommand',
            "\xE2\x9D\x8C Удалить импорт из VK" => 'DelVkCommand',
            '/help' => 'HelpCommand',
            "\xE2\x9D\x93 Помощь" => 'HelpCommand',
            '/default' => 'DefaultCommand'
        );

        $setVkState = $defaultState;
        $setVkState['/default'] = 'SetVkCommand';

        $delVkState = $defaultState;
        $delVkState['/default'] = 'DelVkCommand';

        $stateMap = array(
            'default' => $defaultState,
            'set_vk_main' => $setVkState,
            'set_vk_telegram' => $setVkState,
            'del_vk_main' => $delVkState
        );

        return $stateMap;
    }
}
