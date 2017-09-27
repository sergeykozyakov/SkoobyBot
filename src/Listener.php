<?php
namespace SkoobyBot;

use SkoobyBot\Config;
use Telegram\Bot\Api;
use Katzgrau\KLogger\Logger;

class Listener
{
    protected $logger = null;
    protected $telegram = null;

    public function __construct()
    {
        try {
            $logger = new Logger(__DIR__ . '/' . Config::getLogDir());
            $this->setLogger($logger);
        } catch (Exception $e) {
            echo 'SkoobyBot logger system does not work! App is stopped.';
            return;
        }

        $token = Config::getTelegramToken();
        if (!$token) {
            $this->getLogger()->error('No Telegram token is specified!');
            return;
        }

        try {
            $telegram = new Api($token);
            $this->setTelegram($telegram);
        } catch (Exception $e) {
            $this->getLogger()->error('Telegram API connection error!');
            return;
        }
    }

    public function getLogger() {
        return $this->logger;
    }

    public function setLogger($logger) {
        $this->logger = $logger;
        return $this;
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
        if (!$this->getTelegram()) {
            $this->getLogger()->error('Cannot receive user message until connection is created!');
            return;
        }
        
        $result = $this->getTelegram()->getWebhookUpdates();
            
        if ($result && isset($result['message'])) {
            $text = $result['message']['text'];
            $chat_id = $result['message']['chat']['id'];
            $user_name = $result['message']['from']['firstname'];
            
            $keyboard = [["\xE2\x9E\xA1 Помощь"]];
            
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
                case "\xE2\x9E\xA1 Помощь":
                    $answer = 'Смотри, основные команды — это /start и /help и пока этого достаточно. В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет.';
                    break;
                default:
                    $answer = 'Я получил твоё сообщение и рассмотрю его :-)';
                    break;
            }

            $this->getTelegram()->sendMessage(['chat_id' => $chat_id, 'text' => $answer, 'reply_markup' => $reply_markup]);
        }
        else {
            $this->getLogger()->error('Cannot read user message!');
        }
    }
}