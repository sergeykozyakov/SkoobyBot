<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Config;
use SkoobyBot\Commands\BaseCommand;

use VK\VK;
use VK\VKException;

class GetVkCommand extends BaseCommand
{
    public function start() {
        if (!$this->getMessage()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $vkAppId = Config::getVkAppId();
        $vkSecret = Config::getVkSecret();
        $vkToken = Config::getVkToken();

        if (!$vkAppId || !$vkSecret || !$vkToken) {
            $this->getLogger()->warning('No VK API tokens were specified!');
            $response = 'Нет ключей доступа для подключения к серверу VK! Извини, это поломка на моей стороне.';

            $this->sendMessage($response);
            return;
        }

        $posts = null;

        try {
            $vk = new VK($vkAppId, $vkSecret, $vkToken);
            $posts = $vk->api('wall.get', array(
                'owner_id' => '3485547',
                'count' => 3,
                'filter' => 'owner',
                'v' => '5.60',
                'lang' => 'ru'
            ));
        } catch (VKException $e) {
            $this->getLogger()->warning('VK API connection error! ' . $e->getMessage());
            $response = 'Не могу подключиться к серверу VK! Попробуй позже.';

            $this->sendMessage($response);
            return;
        }

        if (!$posts || !isset($posts['response']) || !isset($posts['response']['items'])) {
            $this->getLogger()->warning('Cannot read received VK API response!');
            $response = 'Не могу получить посты из VK! ' .
                'Такое бывает, если у пользователя закрыта стена или удалена страница, попробуй потом ещё раз.';

            $this->sendMessage($response);
            return;
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
                $this->sendMessage($postText);
            }

            if (count($postPhotos) > 0) {
                foreach($postPhotos as $postPhoto) {
                    $this->sendPhoto($postPhoto['url'], $postPhoto['text']);
                }
            }

            if (!$postText && count($postPhotos) == 0) {
                $link = '<a href="https://vk.com/id3485547?w=wall3485547_' . $postId . '%2Fall">' .
                    'https://vk.com/id3485547?w=wall3485547_' . $postId . '%2Fall</a>';

                $this->sendMessage($link, 'HTML', true);
            }
        }
    }
}
