<?php
namespace SkoobyBot;

use SkoobyBot\Config;
use Telegram\Bot\Api;
use Katzgrau\KLogger\Logger;
use Psr\Log\LogLevel;

class Listener
{
    protected $logger = null;
    protected $api = null;

    public function __construct()
    {
        try {
            $logDir = Config::getLogDir();
            $logger = new Logger(__DIR__ . '/' . $logDir, LogLevel::WARNING);
            $this->setLogger($logger);
        } catch (\Exception $e) {
            echo 'SkoobyBot Logger system does not work! App is stopped.';
            return;
        }

        $token = Config::getTelegramToken();
        if (!$token) {
            $this->getLogger()->error('No Telegram token is specified!');
            return;
        }

        try {
            $api = new Api($token);
            $this->setApi($api);
        } catch (\Exception $e) {
            $this->getLogger()->error('Telegram API connection error! '. $e->getMessage());
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
    
    public function getApi() {
        return $this->api;
    }

    public function setApi($api) {
        $this->api = $api;
        return $this;
    }

    public function getUpdates()
    {
        if (!$this->getApi()) {
            $this->getLogger()->error('Cannot receive user message until connection is created!');
            return;
        }
        
        $result = $this->getApi()->getWebhookUpdates();
            
        if ($result && isset($result['message'])) {
            $text = $result['message']['text'];
            $chat_id = $result['message']['chat']['id'];
            
            $keyboard = [["\xE2\x9E\xA1 Помощь"]];
            
            $answer = '';
            $reply_markup = null;

            switch ($text) {
                case '/start':
                    $answer = 'Привет! Я SkoobyBot. Как дела?';
                    $reply_markup = $this->getApi()->replyKeyboardMarkup(
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

            try {
                $this->getApi()->sendMessage(['chat_id' => $chat_id, 'text' => $answer, 'reply_markup' => $reply_markup]);
            } catch (\Exception $e) {
                $this->getLogger()->error('Cannot send bot message via Telegram API! '. $e->getMessage());
            }
        }
        else {
            $this->getLogger()->warning('Cannot read user message! Perhaps you started this page from browser.');
        }
    }
}