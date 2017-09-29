<?php
namespace SkoobyBot;

use SkoobyBot\Config;

use VK\VK;
use VK\VKException;

use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

class Listener
{
    protected $logger = null;
    protected $api = null;

    public function __construct($logger) {
        if (!$logger) {
            throw new \Exception('[ERROR] Logger component is not defined!');
        }

        $this->setLogger($logger);
        $token = Config::getTelegramToken();

        if (!$token) {
            $this->getLogger()->error('No Telegram API token was specified!');
            throw new \Exception('[ERROR] No Telegram API token was specified!');
        }

        try {
            $api = new Api($token);
            $this->setApi($api);
        } catch (\Exception $e) {
            $this->getLogger()->error('Telegram API connection error! ' . $e->getMessage());
            throw new TelegramSDKException('[ERROR] Telegram API connection error!');
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
            $this->getLogger()->error('Cannot receive message until Telegram API is connected!');
            throw new \Exception('[ERROR] Cannot receive message until Telegram API is connected!');
        }

        $result = $this->getApi()->getWebhookUpdates();

        if (!$result || !$result->getMessage()) {
            $this->getLogger()->error('Cannot read received Telegram API message!');
            throw new \Exception('[ERROR] Cannot read received Telegram API message!');
        }

        $text = $result->getMessage()->getText();
        $chatId = $result->getMessage()->getChat()->getId();
        $firstName = $result->getMessage()->getChat()->getFirstName();

        $keyboard = [["\xE2\x9E\xA1 Помощь"], ["\xE2\x9E\xA1 Последний пост VK"]];

        $answer = null;
        $replyMarkup = null;

        switch ($text) {
            case '/start':
                $answer = 'Привет, ' . $firstName . '! Я Skooby Bot. Как дела?';
                $replyMarkup = $this->getApi()->replyKeyboardMarkup(
                    ['keyboard' => $keyboard, 'resize_keyboard' => true, 'one_time_keyboard' => false]
                );
                break;
            case '/help':
            case "\xE2\x9E\xA1 Помощь":
                $answer = 'Смотри, основные команды — это /start и /help и пока этого достаточно. ' .
                    'В принципе, можно любой текст и картинку мне отправить. Увидишь, что будет. ' .
                    'Ещё недавно появился запрос последнего поста из VK — это /getPost.';
                break;
            case '/getPost':
            case "\xE2\x9E\xA1 Последний пост VK":
                // Начало неформатированного небезопасного кода
                $vkAppId = Config::getVkAppId();
                $vkSecret = Config::getVkSecret();
                $vkToken = Config::getVkToken();

                if (!$vkAppId || !$vkSecret || !$vkToken) {
                    $this->getLogger()->warning('No VK API tokens were specified!');
                    $answer = 'Нет ключей доступа для подключения к серверу VK! Извини, это поломка на моей стороне.';
                    break;
                }

                try {
                    $vk = new VK($vkAppId, $vkSecret, $vkToken);
                    $posts = $vk->api('wall.get', array(
                        'owner_id' => '3485547',
                        'count' => 3,
                        'filter' => 'owner',
                        'v' => '5.60',
                        'lang' => 'ru'
                    ));

                    if (!$posts || !isset($posts['response']) || !isset($posts['response']['items'])) {
                        $this->getLogger()->warning('Cannot read received VK API response!');
                        $answer = 'Не могу получить посты из VK! ' .
                            'Такое бывает, если у пользователя закрыта стена или удалена страница, попробуй потом ещё раз.';
                        break;
                    }

                    foreach ($posts['response']['items'] as $post) {
                        if ($post['post_type'] != 'post' || isset($post['copy_history'])) continue;

                        $postId = $post['id'];
                        $postText = $post['text']; // TODO: распарсить ссылки
                        $postPhotos = array();

                        if (isset($post['attachments'])) {
                            foreach ($post['attachments'] as $attachment) {
                                switch ($attachment['type']) {
                                    case 'photo':
                                        $attachmentText = $attachment['photo']['text'];
                                        $attachmentUrl = '';

                                        $photoSizes = array(1280, 807, 604, 130, 75);
                                        foreach ($photoSizes as $photoSize) {
                                            if (isset($attachment['photo']['photo_' . $photoSize])) {
                                                $attachmentUrl = $attachment['photo']['photo_' . $photoSize];
                                                break;
                                            }
                                        }

                                        $postPhotos[] = array('text' => $attachmentText, 'url' => $attachmentUrl);
                                        break;
                                    default:
                                        // TODO: подумать об обработке видео, ссылок, ...
                                        break;
                                }
                            }
                        }
                        
                        if ($postText) {
                            try {
                                $this->getApi()->sendMessage(['chat_id' => $chatId, 'text' => $postText]);
                            } catch (TelegramSDKException $e) {
                                $this->getLogger()->error('Cannot send message via Telegram API! ' . $e->getMessage());
                                throw new \Exception('[ERROR] Cannot send message via Telegram API!');
                            }
                        }
                        
                        if (count($postPhotos) > 0) {
                            foreach($postPhotos as $postPhoto) {
                                try {
                                    $this->getApi()->sendPhoto(['chat_id' => $chatId, 'caption' => $postPhoto['text'], 'photo' => $postPhoto['url']]);
                                } catch (TelegramSDKException $e) {
                                    $this->getLogger()->error('Cannot send photo via Telegram API! ' . $e->getMessage());
                                    throw new \Exception('[ERROR] Cannot send photo via Telegram API!');
                                }
                            }
                        }
                        
                        if (!$postText && count($postPhotos) == 0) {
                            try {
                                $this->getApi()->sendMessage([
                                    'chat_id' => $chatId,
                                    'parse_mode' => 'HTML',
                                    'disable_web_page_preview' => true,
                                    'text' => '<a href="https://vk.com/id3485547?w=wall3485547_' . $postId . '%2Fall">https://vk.com/id3485547?w=wall3485547_' . $postId . '%2Fall</a>'
                                ]);
                            } catch (TelegramSDKException $e) {
                                $this->getLogger()->error('Cannot send link via Telegram API! ' . $e->getMessage());
                                throw new \Exception('[ERROR] Cannot send link via Telegram API!');
                            }
                        }
                    }
                } catch (VKException $e) {
                    $this->getLogger()->warning('VK API connection error! ' . $e->getMessage());
                    $answer = 'Не могу подключиться к серверу VK! Попробуй позже.';
                }
                // Конец неформатированного небезопасного кода
                break;
            default:
                $answer = 'Я получил твоё сообщение! Если нужна помощь, то набери /help.';
                break;
        }

        if ($answer) {
            try {
                $this->getApi()->sendMessage(['chat_id' => $chatId, 'text' => $answer, 'reply_markup' => $replyMarkup]);
            } catch (TelegramSDKException $e) {
                $this->getLogger()->error('Cannot send message via Telegram API! ' . $e->getMessage());
                throw new \Exception('[ERROR] Cannot send message via Telegram API!');
            }
        }
    }
}
