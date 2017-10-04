<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Actions\BaseAction;
use SkoobyBot\Commands\CommandFactory;

class Listener extends BaseAction
{
    public function start() {
        $result = $this->getApi()->getWebhookUpdates();

        if (!$result->getMessage()) {
            $this->getLogger()->error('Cannot read received Telegram API message!');
            throw new \Exception('[ERROR] Cannot read received Telegram API message!');
        }

        $text = $result->getMessage()->getText();
        $chatId = $result->getMessage()->getChat()->getId();
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
                    '(chat_id: ' . $chatId . ') State ' . $botState . ' command /default not found!'
                );
                throw new \Exception('[ERROR] State ' . $botState . ' command /default not found!');
            }
            $action = '/default';
        }

        $commandParams = $foundState[$action];

        if (!$commandParams || !isset($commandParams['class']) || !$commandParams['class'])  {
            $this->getLogger()->error(
                '(chat_id: ' . $chatId . ') State ' . $botState . ' command ' . $action . ' class not found!'
            );
            throw new \Exception('[ERROR] State ' . $botState . ' command ' . $action . ' class not found!');
        }

        try {
            $command = CommandFactory::get($commandParams['class'], $this->getApi(), $this->getLogger());
            $command
                ->setMessage($result->getMessage())
                ->setReplyMarkup(isset($commandParams['markup']) ? $commandParams['markup'] : null)
                ->start();
        } catch (\Exception $e) {
            $this->getLogger()->error(
                '(chat_id: ' . $chatId . ') Cannot execute bot ' . $action . ' command: ' . $e->getMessage()
            );
            throw new \Exception('[ERROR] Cannot execute bot ' . $action . ' command: ' . $e->getMessage());
        }
    }

    private function getMarkup($keyboard, $isResize = true, $isOneTime = false) {
        if (!$keyboard) return null;

        return $this->getApi()->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => $isResize,
            'one_time_keyboard' => $isOneTime
        ]);
    }

    private function getStateMap() {
        $defaultKeyboard = [
            ["\xE2\x9E\x95 Настроить импорт из VK"], ["\xE2\x98\x95 Последний пост VK"],
            ["\xE2\x9D\x8C Удалить импорт из VK"], ["\xE2\x9D\x93 Помощь"]
        ];

        $vkKeyboard = $defaultKeyboard;
        array_splice($vkKeyboard, 0, 3);

        $vkAfterKeyboard = $defaultKeyboard;
        array_splice($vkAfterKeyboard, 0, 1);

        $getVkKeyboard = $defaultKeyboard;
        array_splice($getVkKeyboard, 1, 1);

        $delVkAfterKeyboard = $defaultKeyboard;
        array_splice($delVkAfterKeyboard, 2, 1);

        $defaultState = array(
            '/start' => array(
                'class' => 'StartCommand', 'markup' => $this->getMarkup($defaultKeyboard)
            ),
            '/setVk' => array(
                'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ),
            "\xE2\x9E\x95 Настроить импорт из VK" => array(
                'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ),
            '/getVk' => array(
                'class' => 'GetVkCommand', 'markup' => $this->getMarkup($getVkKeyboard)
            ),
            "\xE2\x98\x95 Последний пост VK" => array(
                'class' => 'GetVkCommand', 'markup' => $this->getMarkup($getVkKeyboard)
            ),
            '/delVk' => array(
                'class' => 'DelVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ),
            "\xE2\x9D\x8C Удалить импорт из VK" => array(
                'class' => 'DelVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ),
            '/help' => array(
                'class' => 'HelpCommand', 'markup' => $this->getMarkup($defaultKeyboard)
            ),
            "\xE2\x9D\x93 Помощь" => array(
                'class' => 'HelpCommand', 'markup' => $this->getMarkup($defaultKeyboard)
            ),
            '/default' => array(
                'class' => 'DefaultCommand', 'markup' => $this->getMarkup($defaultKeyboard)
            )
        );

        $setVkMainState = $defaultState;
        $setVkMainState['/default'] = array(
            'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
        );

        $setVkTelegramState = $setVkMainState;
        $setVkTelegramState['/default'] = array(
            'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkAfterKeyboard)
        );

        $delVkMainState = $defaultState;
        $delVkMainState['/default'] = array(
            'class' => 'DelVkCommand', 'markup' => $this->getMarkup($delVkAfterKeyboard)
        );

        $stateMap = array(
            'default' => $defaultState,
            'set_vk_main' => $setVkMainState,
            'set_vk_telegram' => $setVkTelegramState,
            'del_vk_main' => $delVkMainState
        );

        return $stateMap;
    }
}
