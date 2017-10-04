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
        $response = $firstName . ', для начала импорта постов VK в твой канал Telegram убедись, ' .
            "что твоя стена или группа открыта для всех.\n\nЕсли у твоей страницы или группы есть короткое имя " .
            '(например vk.com/my_name), то напиши мне это имя. Если у твоей страницы есть только id ' . 
            '(например vk.com/id000000 или vk.com/club000000), то напиши мне только цифры. Но внимание! '.
            'Если хочешь делать импорт из группы, то перед цифрами обязательно поставь знак минус.';

        $responseMain = 'Спасибо! Теперь убедись, что у тебя создан Telegram канал и у твоего канала ' .
            "задано имя вида @channel_name.\n\nВсё в порядке? Теперь очень важно! Тебе нужно добавить меня " .
            "в качестве ещё одного админа твоего канала (меня можно найти как @skooby_bot).\n\n" .
            'Ну всё, теперь окончательное действие — напиши мне имя своего канала (начни с символа @).';

        $responseTelegram = 'Поздравляю! Теперь импорт настроен и если ты всё указал правильно, то каждые ' .
            "10 минут я буду проверять твою стену или группу на предмет новых постов и отправлять их в твой канал.\n\n" .
            'Ты можешь уже сейчас проверить работоспособность привязки, выполнив /getVk.';

        $responseFailed = "Ты мне прислал что-то не то \xF0\x9F\x98\xB5! Попробуй ещё раз.";

        try {
            $state = $this->getBotState();
            $text = $this->getMessage()->getText();

            if ($state == 'set_vk_main') {
                if (!preg_match('/^[a-zA-Z0-9_]+$/', $text)) {
                    $this->sendMessage($responseFailed);
                    return;
                }

                $this->sendMessage($responseMain);

                $this->getDatabase()->setVkWall($this->getChatId(), $text);
                $this->getDatabase()->setBotState($this->getChatId(), 'set_vk_telegram');
            }
            else if ($state == 'set_vk_telegram') {
                if (!preg_match('/^[a-zA-Z0-9_@]+$/', $text)) {
                    $keyboard = Listener::getDefaultKeyboard();
                    array_splice($keyboard, 0, 3);

                    $this->setReplyMarkup(
                        $this->getApi()->replyKeyboardMarkup([
                            'keyboard' => $keyboard,
                            'resize_keyboard' => true,
                            'one_time_keyboard' => false
                        ])
                    );

                    $this->sendMessage($responseFailed);
                    return;
                }

                $this->sendMessage($responseTelegram);

                $this->getDatabase()->setChannel($this->getChatId(), $text);
                $this->getDatabase()->setBotState($this->getChatId(), 'default');
            }
            else {
                $this->sendMessage($response, null, true);
                $this->getDatabase()->setBotState($this->getChatId(), 'set_vk_main');
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
