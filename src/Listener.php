<?php
namespace SkoobyBot;

use SkoobyBot\Config;
use Telegram\Bot\Api;

class Listener
{
    protected $telegram = null;

    public function __construct()
    {
        $token = Config::getTelegramToken();
        if (!$token) {
            echo 'LOG: No Telegram token is specified!';
            return;
        }

        $telegram = new Api($token);
        $this->setTelegram($telegram);
    }

    public function getTelegram() {
        return $this->telegram;
    }

    public function setTelegram($telegram) {
        $this->telegram = $telegram;
        return $this;
    }

    public function getUpdates()
    {
        if ($this->getTelegram()) {
            $result = $this->getTelegram()->getWebhookUpdates();
            
            if ($result && isset($result['message'])) {
                $text = $result['message']['text'];
                $chat_id = $result['message']['chat']['id'];
                $user_name = $result['message']['from']['username'];
                
                $keyboard = [["\xE2\x9E\xA1 Старт"], ["\xE2\x9E\xA1 Помощь"]];
                
                $answer = '';
                $reply_markup = null;

                switch ($text) {
                    case '/start':
                    case "\xE2\x9E\xA1 Старт":
                        $answer = 'Привет, '.$user_name.'! Я SkoobyBot. Как дела?';
                        $reply_markup = $this->getTelegram()->replyKeyboardMarkup(
                            ['keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false]
                        );
                        break;
                    case '/help':
                    case "\xE2\x9E\xA1 Помошь":
                        $answer = 'Смотри, основные команды — это /start и /help и пока этого достаточно. В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет.';
                        break;
                    default:
                        $answer = 'Я получил твоё сообщение и рассмотрю его :-)';
                        break;
                }

                $this->getTelegram()->sendMessage(['chat_id' => $chat_id, 'text' => $answer, 'reply_markup' => $reply_markup]);
            }
            else {
                echo 'LOG: Cannot read user message!';
            }
        }
        else {
            echo 'LOG: Cannot receive user message until connection is created!';
        }
    }
}