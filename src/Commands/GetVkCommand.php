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

        $wallIds = array('sergeykozyakov', '-86529522');
        foreach($wallIds as $wallId) {
            if (!$this->readWall($wallId, $vkAppId, $vkSecret, $vkToken)) break;
        }
    }

    private function readWall($wallId, $vkAppId, $vkSecret, $vkToken) {
        $postList = array();

        $limit = 3;
        $offset = 1;

        $domain = $wallId && !is_numeric($wallId) ? $wallId : null;

        while($offset > -1 && $offset <= 4) {
            $posts = null;

            try {
                $vk = new VK($vkAppId, $vkSecret, $vkToken);
                $posts = $vk->api('wall.get', array(
                    'owner_id' => !$domain ? $wallId : null,
                    'domain' => $domain,
                    'count' => $limit,
                    'offset' => $offset,
                    'filter' => 'owner',
                    'v' => '5.60',
                    'lang' => 'ru'
                ));
            } catch (VKException $e) {
                $this->getLogger()->warning('VK API connection error! ' . $e->getMessage());
                $response = 'Не могу подключиться к серверу VK! Попробуй позже.';

                $this->sendMessage($response);
                return false;
            }

            if (!$posts || !isset($posts['response']) || !isset($posts['response']['items'])) {
                $this->getLogger()->warning('Cannot read received VK API response!');
                $response = 'Не могу получить посты из VK! ' .
                    'Такое бывает, если у пользователя закрыта стена или удалена страница, попробуй потом ещё раз.';

                $this->sendMessage($response);
                return true;
            }

            foreach ($posts['response']['items'] as $post) {
                if ($post['post_type'] != 'post' || isset($post['copy_history'])) continue;

                $postId = $post['id'];
                $ownerId = $post['owner_id'];
                $postText = $post['text']; // TODO: распарсить ссылки [club1959|Радио Рекорд]
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

                $postList[] = array('id' => $postId, 'ownerId' => $ownerId, 'text' => $postText, 'photos' => $postPhotos);
            }

            $offset += $limit;
        }

        foreach (array_reverse($postList) as $item) {
            try {
                if ($item['text']) {
                    $this->sendMessage($item['text']);
                }

                if (count($item['photos']) > 0) {
                    foreach($item['photos'] as $postPhoto) {
                        $this->sendPhoto($postPhoto['url'], $postPhoto['text']);
                    }
                }

                //if (!$item['text'] && count($item['photos']) == 0) {
                    $isGroup = intval($item['ownerId']) < 0;
                    $ownerAbsId = abs(intval($item['ownerId']));
                    $ownerUrl = $domain ? $domain : (($isGroup ? 'club' : 'id') . $ownerAbsId);

                    $fullUrl = 'https://vk.com/' . $ownerUrl . '?w=wall' . $item['ownerId'] . '_' . $item['id'];
                    $link = '<a href="' . $fullUrl . '">' . $fullUrl . '</a>';

                    $this->sendMessage($link, 'HTML', true);
                //}
            } catch (\Exception $e) {
                $this->getLogger()->warning('Cannot send photo/message to a specified channel via Telegram API!');
                $response = 'Не могу отправить пост в канал Telegram! ' .
                    'Такое бывает, если у Skooby Bot нет прав на запись в канал или канал указан неверно.';

                $this->sendMessage($response);
                return true;
            }
        }

        return true;
    }
}
