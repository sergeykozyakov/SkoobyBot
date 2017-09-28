<?php
namespace SkoobyBot;

use SkoobyBot\Config;

use Telegram\Bot\Api;

class Listener
{
    protected $logger = null;
    protected $api = null;

    public function __construct($logger) {
        if (!$logger) {
            throw new \Exception('[ERROR] Skooby Bot Logger is not defined!');
        }

        $this->setLogger($logger);
        $token = Config::getTelegramToken();

        if (!$token) {
            $this->getLogger()->error('No Telegram token is specified!');
            throw new \Exception('[ERROR] No Telegram token is specified!');
        }

        try {
            $api = new Api($token);
            $this->setApi($api);
        } catch (\Exception $e) {
            $this->getLogger()->error('Telegram API connection error! ' . $e->getMessage());
            throw new \Exception('[ERROR] Telegram API connection error!');
        }
    }

    protected function getLogger() {
        return $this->logger;
    }

    protected function setLogger($logger) {
        $this->logger = $logger;
        return $this;
    }

    protected function getApi() {
        return $this->api;
    }

    protected function setApi($api) {
        $this->api = $api;
        return $this;
    }

    public function getUpdates() {
        if (!$this->getApi()) {
            $this->getLogger()->error('Cannot receive user message until connection is created!');
            throw new \Exception('[ERROR] Cannot receive user message until connection is created!');
        }

        $result = $this->getApi()->getWebhookUpdates();

        if (!$result || !$result->getMessage()) {
            $this->getLogger()->warning('Cannot read user message! Perhaps you started this page from outside Telegram.');
            return;
        }

        $text = $result->getMessage()->getText();
        $chat_id = $result->getMessage()->getChat()->getId();
        $first_name = $result->getMessage()->getChat()->getFirstName();

        $keyboard = [["\xE2\x9E\xA1 Помощь"], ["\xE2\x9E\xA1 Последний пост VK"]];

        $answer = '';
        $reply_markup = null;

        switch ($text) {
            case '/start':
                $answer = 'Привет, ' . $first_name . '! Я Skooby Bot. Как дела?';
                $reply_markup = $this->getApi()->replyKeyboardMarkup(
                    ['keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false]
                );
                break;
            case '/help':
            case "\xE2\x9E\xA1 Помощь":
                $answer = 'Смотри, основные команды — это /start и /help и пока этого достаточно. ' .
                    'В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет.\n\n' .
                    'Ещё недавно появился запрос последнего поста из VK — это /getPost.';
                break;
            case '/getPost':
            case "\xE2\x9E\xA1 Последний пост VK":
                $answer = 'Здесь будет выгрузка последнего поста из VK...';
                break;
            default:
                $answer = 'Я получил твоё сообщение и рассмотрю его :-)';
                break;
        }

        try {
            $this->getApi()->sendMessage(['chat_id' => $chat_id, 'text' => $answer, 'reply_markup' => $reply_markup]);
        } catch (\Exception $e) {
            $this->getLogger()->error('Cannot send bot message via Telegram API! ' . $e->getMessage());
            throw new \Exception('[ERROR] Cannot send bot message via Telegram API!');
        }
    }
}
