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

        $responseMain = 'Спасибо! Теперь убедись, что у тебя создан Telegram канал и у твоего канала ' .
            "задано имя вида @channel_name.\n\nВсё в порядке? Теперь очень важно! Тебе нужно добавить меня " .
            "в качестве ещё одного админа твоего канала (меня можно найти как @skooby_bot).\n\n" .
            'Ну всё, теперь окончательное действие — напиши мне имя своего канала (начни с символа @).';

        $responseMainFailed = 'Ты мне прислал что-то не то! Попробуй ещё раз.';

        try {
            $state = $this->getBotState();
            $text = $this->getMessage()->getText();

            if ($state == 'default') {
                $this->sendMessage($response);
                $this->getDatabase()->setBotState($this->getChatId(), 'set_vk_main');
            }
            else if ($state == 'set_vk_main') {
                $newState = 'default';
                if (!$text) {
                    $responseMain = $responseMainFailed;
                    $newState = 'set_vk_main';
                    return;
                }

                $this->sendMessage($responseMain);

                $this->getDatabase()->setVkWall($this->getChatId(), $text);
                $this->getDatabase()->setBotState($this->getChatId(), $newState);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
