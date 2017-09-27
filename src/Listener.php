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
            echo 'LOG: No Telegram token specified!';
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

    public function getMe()
    {
        if ($this->getTelegram()) {
            $user = $this->getTelegram()->getMe();
            
            if ($user) {
                echo $user->getFirstName();
            }
            else {
                echo 'LOG: Cannot call method getFirstName User entity is empty!';
            }
        }
        else {
            echo 'LOG: Cannot call method getMe until connection created!';
        }
    }

    public function getUpdates()
    {
        if ($this->getTelegram()) {
            $result = $this->getTelegram()->getWebhookUpdates();
            
            if ($result && isset($result['message'])) {
                $chat_id = $result['message']['chat']['id'];
                $text = $result['message']['text'];

                $this->getTelegram()->sendMessage(['chat_id' => $chat_id, 'text' => 'Вы написали: '.$text]);
            }
            else {
                echo 'LOG: Cannot read message until request is empty!';
            }
        }
        else {
            echo 'LOG: Cannot call method getWebhookUpdates until connection created!';
        }
    }
}