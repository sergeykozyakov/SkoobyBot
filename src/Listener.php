<?php
namespace SkoobyBot;

use SkoobyBot\Config;

use Telegram\Bot\Api;
use VK\VK;
use VK\VKException;

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
                    'В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет.' .
                    'Ещё недавно появился запрос последнего поста из VK — это /getPost.';
                break;
            case '/getPost':
            case "\xE2\x9E\xA1 Последний пост VK":
                // Начало неформатированного небезопасного кода
                $vk_token = Config::getVkToken();
                // проверить наличие токена, все имена в CamelCase

                try {
                    $vk = new VK('6198731', 'ReoT7Z9tDWFMtszboXEE', $vk_token);
                    $posts = $vk->api('wall.get', array(
                        'owner_id' => '3485547',
                        'count' => 10,
                        'filter' => 'owner',
                        'v' => '5.60',
                        'lang' => 'ru'
                    ));

                    if (!$posts || !isset($posts['response']) || !isset($posts['response']['items'])) {
                        $answer = 'Не могу получить последний пост из VK. Извини. Упал на этапе обработки.';
                        break;
                    }

                    foreach ($posts['response']['items'] as $post) {
                        if ($post['post_type'] != 'post' || isset($post['copy_history'])) continue;

                        $post_id = $post['id'];
                        $post_text = $post['text']; // распарсить ссылки
                        $post_photos = array();

                        if (isset($post['attachments'])) {
                            foreach ($post['attachments'] as $attachment) {
                                switch ($attachment['type']) {
                                    case 'photo':
                                        $attachment_text = $attachment['photo']['text'];
                                        $attachment_url = '';

                                        $photo_size_arr = array(1280, 807, 604, 130, 75);
                                        foreach ($photo_size_arr as $photo_size) {
                                            if (isset($attachment['photo']['photo_' . $photo_size])) {
                                                $attachment_url = $attachment['photo']['photo_' . $photo_size];
                                                break;
                                            }
                                        }

                                        $post_photos[] = array('text' => $attachment_text, 'url' => $attachment_url);
                                        break;
                                    default:
                                        // подумать об обработке видео, ссылок, ...
                                        break;
                                }
                            }
                        }
                        
                        if ($post_text) {
                            try {
                                $this->getApi()->sendMessage(['chat_id' => $chat_id, 'text' => $post_text]);
                            } catch (\Exception $e) {
                                $this->getLogger()->error('Cannot send bot message via Telegram API! ' . $e->getMessage());
                                throw new \Exception('[ERROR] Cannot send bot message via Telegram API!');
                            }
                        }
                        
                        if (count($post_photos) > 0) {
                            foreach($post_photos as $post_photo) {
                                try {
                                    $this->getApi()->sendPhoto(['chat_id' => $chat_id, 'caption' => $post_photo['text'], 'photo' => $post_photo['url']]);
                                } catch (\Exception $e) {
                                    $this->getLogger()->error('Cannot send bot message via Telegram API! ' . $e->getMessage());
                                    throw new \Exception('[ERROR] Cannot send bot message via Telegram API!');
                                }
                            }
                        }
                        
                        if (!$post_text && count($post_photos) == 0) {
                            try {
                                $this->getApi()->sendMessage([
                                    'chat_id' => $chat_id,
                                    'parse_mode' => 'HTML',
                                    'disable_web_page_preview' => true,
                                    'text' => '<a href="https://vk.com/id3485547?w=wall3485547_' . $post_id . '%2Fall">https://vk.com/id3485547?w=wall3485547_' . $post_id . '%2Fall</a>'
                                ]);
                            } catch (\Exception $e) {
                                $this->getLogger()->error('Cannot send bot message via Telegram API! ' . $e->getMessage());
                                throw new \Exception('[ERROR] Cannot send bot message via Telegram API!');
                            }
                        }
                    }
                } catch (VKException $e) {
                    $this->getLogger()->error('VK API connection error! ' . $e->getMessage());
                    $answer = 'Не могу получить последний пост из VK. Извини. Даже не смог подключиться к серверу.';
                }
                // Конец неформатированного небезопасного кода
                break;
            default:
                $answer = 'Я получил твоё сообщение и рассмотрю его :-)';
                break;
        }

        if ($answer) {
            try {
                $this->getApi()->sendMessage(['chat_id' => $chat_id, 'text' => $answer, 'reply_markup' => $reply_markup]);
            } catch (\Exception $e) {
                $this->getLogger()->error('Cannot send bot message via Telegram API! ' . $e->getMessage());
                throw new \Exception('[ERROR] Cannot send bot message via Telegram API!');
            }
        }
    }
}
