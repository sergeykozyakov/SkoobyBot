<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class HelpCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = 'Смотри, основные команды — это /start и /help. На всякий случай, твой код чата — ' . $this->getChatId() . '. ' .
            'Основное назначение Skooby Bot — это /setVk, /getVk, /delVk. Выполнится привязка к VK, ' .
            'запрос последнего поста из VK, и отвязка от VK. ' .
            'В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет.';

        try {
            $this->sendMessage($response);
            $this->getDatabase()->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
