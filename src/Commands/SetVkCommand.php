<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Commands\BaseCommand;

class SetVkCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $firstName = $this->getMessage()->getChat()->getFirstName();
        $response = $firstName . ', для начала импорта постов VK в твой канал Telegram убедись, ' .
            "что твоя стена или группа открыта для всех.\n\nЕсли у твоей страницы или группы есть короткое имя " .
            '(например vk.com/my_name), то напиши мне это имя. Если у твоей страницы есть только id ' . 
            '(например vk.com/id123456 или vk.com/club987654), то напиши мне только цифры. Но внимание! '.
            'Если хочешь делать импорт из группы, то перед цифрами обязательно поставь знак минус.';

        try {
            $this->sendMessage($response);

            $this->getDatabase()->setBotState($this->getChatId(), 'set_vk_main');
            //$this->getDatabase()->addUser($this->getChatId());
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
