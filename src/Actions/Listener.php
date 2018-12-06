<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Actions\BaseAction;
use SkoobyBot\Actions\InlineAction;

use SkoobyBot\Commands\CommandFactory;
use SkoobyBot\Languages\Language;

class Listener extends BaseAction
{
    private $language = null;

    public function start() {
        $result = $this->getApi()->getWebhookUpdates();

        if ($result->getCallbackQuery()) {
            try {
                $inlineAction = new InlineAction($this->getLogger());
                $inlineAction->setCallbackQuery($result->getCallbackQuery());
                $inlineAction->start();
            } catch (\Exception $e) {
                $channel = $result->getCallbackQuery()->getMessage()->getChat()->getId();
                $this->getLogger()->error(
                    '(channel: ' . $channel . ') Cannot execute bot inline callback command: ' . $e->getMessage()
                );
                throw new \Exception('[ERROR] Cannot execute bot inline callback command: ' . $e->getMessage());
            }
            return;
        }

        if (!$result->getMessage()) {
            $this->getLogger()->error('Cannot read received Telegram API message!');
            throw new \Exception('[ERROR] Cannot read received Telegram API message!');
        }

        $text = $result->getMessage()->getText();
        $chatId = $result->getMessage()->getChat()->getId();
        $languageCode = $result->getMessage()->getFrom()->getLanguageCode();

        $this->language = Language::getInstance();

        try {
            $this->language
                ->setLanguage($languageCode)
                ->init();
        } catch (\Exception $e) {
            $this->getLogger()->error('(chat_id: ' . $chatId . ') ' . $e->getMessage());
            throw new \Exception('[ERROR] ' . $e->getMessage());
        }

        $botState = '';
        $isConnected = false;

        try {
            $user = $this->getDatabase()->getUser($chatId);
            $botState = (isset($user['bot_state']) && $user['bot_state']) ? $user['bot_state'] : 'default';
            $isConnected = isset($user['vk_wall']) && $user['vk_wall'] && isset($user['channel']) && $user['channel'];
        } catch (\Exception $e) {
            $this->getLogger()->error('(chat_id: ' . $chatId . ') ' . $e->getMessage());
            throw new \Exception('[ERROR] ' . $e->getMessage());
        }

        $stateMap = $this->getStateMap($isConnected);

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
            if ($action != '/default') {
                $this->getDatabase()->setBotState($chatId, 'default');
            }

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

    public static function getDefaultKeyboard($isConnected, $language) {
        $keyboard = [];

        $addVk = 'add VK';
        $editVk = 'edit VK';
        $getVk = 'get VK';
        $delVk = 'del VK';
        $help = 'help';
        $cancel = 'cancel';

        if ($language) {
            try {
                $addVk = $language->get('add_vk_key');
                $editVk = $language->get('edit_vk_key');
                $getVk = $language->get('get_vk_key');
                $delVk = $language->get('del_vk_key');
                $help = $language->get('help_key');
                $cancel = $language->get('cancel_key');
            } catch (\Exception $e) {}
        }

        if ($isConnected) {
            $keyboard = [
                ["\xE2\x9C\x8F " . $editVk], ["\xE2\x98\x95 " . $getVk], ["\xE2\x9D\x8C " . $delVk],
                ["\xE2\x9D\x93 " . $help], ["\xE2\x9B\x94 " . $cancel]
            ];
        }
        else {
            $keyboard = [
                ["\xE2\x9E\x95 " . $addVk], ["\xE2\x9D\x93 " . $help], ["\xE2\x9B\x94 " . $cancel]
            ];
        }

        return $keyboard;
    }

    private function getMarkup($keyboard, $isResize = true, $isOneTime = false) {
        if (!$keyboard) return null;

        return $this->getApi()->replyKeyboardMarkup([
            'keyboard' => $keyboard,
            'resize_keyboard' => $isResize,
            'one_time_keyboard' => $isOneTime
        ]);
    }

    private function getStateMap($isConnected) {
        $keys = self::getDefaultKeyboard(false, $this->language);
        $connKeys = self::getDefaultKeyboard(true, $this->language);

        $defaultKeyboard = self::getDefaultKeyboard($isConnected, $this->language);
        $defaultTriggeredKeyboard = self::getDefaultKeyboard(!$isConnected, $this->language);

        $keyboard = $defaultKeyboard;
        array_splice($keyboard, -1);

        $triggeredKeyboard = $defaultTriggeredKeyboard;
        array_splice($triggeredKeyboard, -1);

        $vkKeyboard = $defaultKeyboard;
        array_splice($vkKeyboard, 0, -1);

        $defaultState = [
            '/start' => [
                'class' => 'StartCommand', 'markup' => $this->getMarkup($keyboard)
            ],
            '/setVk' => [
                'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ],
            $keys[0][0] => [
                'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ],
            $connKeys[0][0] => [
                'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ],
            '/getVk' => [
                'class' => 'GetVkCommand', 'markup' => $this->getMarkup($keyboard)
            ],
            $connKeys[1][0] => [
                'class' => 'GetVkCommand', 'markup' => $this->getMarkup($keyboard)
            ],
            '/delVk' => [
                'class' => 'DelVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ],
            $connKeys[2][0] => [
                'class' => 'DelVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
            ],
            '/cancel' => [
                'class' => 'CancelCommand', 'markup' => $this->getMarkup($keyboard)
            ],
            $keys[2][0] => [
                'class' => 'CancelCommand', 'markup' => $this->getMarkup($keyboard)
            ],
            '/help' => [
                'class' => 'HelpCommand', 'markup' => $this->getMarkup($keyboard)
            ],
            $keys[1][0] => [
                'class' => 'HelpCommand', 'markup' => $this->getMarkup($keyboard)
            ],
            '/default' => [
                'class' => 'DefaultCommand', 'markup' => $this->getMarkup($keyboard)
            ]
        ];

        $setVkMainState = $defaultState;
        $setVkMainState['/default'] = [
            'class' => 'SetVkCommand', 'markup' => $this->getMarkup($vkKeyboard)
        ];

        $setVkTelegramState = $setVkMainState;
        $setVkTelegramState['/default'] = [
            'class' => 'SetVkCommand', 'markup' => $this->getMarkup($isConnected ? $keyboard : $triggeredKeyboard)
        ];

        $delVkMainState = $defaultState;
        $delVkMainState['/default'] = [
            'class' => 'DelVkCommand', 'markup' => $this->getMarkup($triggeredKeyboard)
        ];

        $stateMap = [
            'default' => $defaultState,
            'set_vk_main' => $setVkMainState,
            'set_vk_telegram' => $setVkTelegramState,
            'del_vk_main' => $delVkMainState
        ];

        return $stateMap;
    }
}
