<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class HelpCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $response = 'Я создан для того, чтобы обеспечивать импорт постов твоей стены или группы VK ' .
            'в канал Telegram. Всё, что тебе нужно — это открытая стена VK и канал в Telegram с коротким именем. ' .
            'Также от тебя требуется добавить меня в админы своего канала и каждые 10 минут я смогу обновлять ' .
            "твой канал автоматически.\n\nТы всегда можешь поменять адреса стены и канала или вообще удалить привязку. " .
            'Используй команду /setVk, чтобы добавить/обновить привязку, /getVk, чтобы проверить работоспособность и ' .
            '/delVk, чтобы отключить сбор постов. Удачи!';

        try {
            $this->sendMessage($response);
            $this->getDatabase()->setBotState($this->getChatId(), 'default');
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
