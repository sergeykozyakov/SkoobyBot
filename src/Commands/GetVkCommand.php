<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Config;
use SkoobyBot\Commands\BaseCommand;

use VK\VK;
use VK\VKException;

class GetVkCommand extends BaseCommand
{
    protected static $limit = 3;
    protected static $photoSizes = array(1280, 807, 604, 130, 75);

    public function start() {
        if (!$this->getMessage() && !$this->getIsCron()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $vkAppId = Config::getVkAppId();
        $vkSecret = Config::getVkSecret();
        $vkToken = Config::getVkToken();

        if (!$vkAppId || !$vkSecret || !$vkToken) {
            $this->getLogger()->warning('No VK API tokens were specified!');

            if (!$this->getIsCron()) {
                $response = 'Нет ключей доступа для подключения к серверу VK! Извини, это поломка на моей стороне.';
                $this->sendMessage($response);
            }
            return;
        }

        // TODO: заглушка - заменить на чтение из БД
        if ($this->getIsCron()) {
            $this->chatId = '@sergeykozyakov_live';
        }

        $wallIds = array('sergeykozyakov', '-86529522');
        foreach($wallIds as $wallId) {
            if (!$this->readWall($wallId, $vkAppId, $vkSecret, $vkToken)) break;
        }
        // TODO: конец заглушки - заменить на чтение из БД
    }

    private function readWall($wallId, $vkAppId, $vkSecret, $vkToken) {
        $offset = 1;
        $postList = array();

        $domain = $wallId && !is_numeric($wallId) ? $wallId : null;

        // TODO: $offset <= 4 заглушка - заменить на проверку даты поста и последней даты БД
        while($offset > -1 && $offset <= 4) {
            $posts = null;

            try {
                $vk = new VK($vkAppId, $vkSecret, $vkToken);
                $posts = $vk->api('wall.get', array(
                    'owner_id' => !$domain ? $wallId : null,
                    'domain' => $domain,
                    'count' => self::$limit,
                    'offset' => $offset,
                    'filter' => 'owner',
                    'v' => '5.60',
                    'lang' => 'ru'
                ));
            } catch (VKException $e) {
                $this->getLogger()->warning('VK API connection error! ' . $e->getMessage());

                if (!$this->getIsCron()) {
                    $response = 'Не могу подключиться к серверу VK! Попробуй позже.';
                    $this->sendMessage($response);
                }
                return false;
            }

            if (!$posts || !isset($posts['response']) || !isset($posts['response']['items'])) {
                $this->getLogger()->warning('Cannot read received VK API response!');

                if (!$this->getIsCron()) {
                    $response = 'Не могу получить посты из VK! ' .
                        'Такое бывает, если у пользователя закрыта стена или удалена страница, попробуй потом ещё раз.';
                    $this->sendMessage($response);
                }
                return true;
            }

            foreach ($posts['response']['items'] as $post) {
                if ($post['post_type'] != 'post' || isset($post['copy_history'])) continue;

                $postId = $post['id'];
                $ownerId = $post['owner_id'];
                $postText = preg_replace('/\[(.+?(?=\|))\|(.+?(?=\]))\]/', '\2', $post['text']);

                $needLink = false;
                $postPhotos = array();

                if (isset($post['attachments'])) {
                    foreach ($post['attachments'] as $attachment) {
                        switch ($attachment['type']) {
                            case 'photo':
                                $attachmentText = $attachment['photo']['text'];
                                $attachmentUrl = '';

                                foreach (self::$photoSizes as $photoSize) {
                                    if (isset($attachment['photo']['photo_' . $photoSize])) {
                                        $attachmentUrl = $attachment['photo']['photo_' . $photoSize];
                                        break;
                                    }
                                }

                                $postPhotos[] = array('text' => $attachmentText, 'url' => $attachmentUrl);
                                break;
                            default:
                                // TODO: подумать об обработке видео, ссылок, ...
                                $needLink = true;
                                break;
                        }
                    }
                }

                $postList[] = array(
                    'id' => $postId,
                    'ownerId' => $ownerId,
                    'text' => $postText,
                    'photos' => $postPhotos,
                    'needLink' => $needLink
                );
            }

            $offset += self::$limit;
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

                if (!$item['text'] && count($item['photos']) == 0 || $items['needLink']) {
                    $isGroup = intval($item['ownerId']) < 0;
                    $ownerAbsId = abs(intval($item['ownerId']));
                    $ownerUrl = $domain ? $domain : (($isGroup ? 'club' : 'id') . $ownerAbsId);

                    $fullUrl = 'https://vk.com/' . $ownerUrl . '?w=wall' . $item['ownerId'] . '_' . $item['id'];
                    $link = '<a href="' . $fullUrl . '">' . $fullUrl . '</a>';

                    $this->sendMessage($link, 'HTML', true);
                }
            } catch (\Exception $e) {
                $this->getLogger()->warning('Cannot send photo/message to a specified channel via Telegram API!');

                if (!$this->getIsCron()) {
                    $response = 'Не могу отправить пост в канал Telegram! ' .
                        'Такое бывает, если у Skooby Bot нет прав на запись в канал или канал указан неверно.';
                    $this->sendMessage($response);
                }
                return true;
            }
        }

        return true;
    }
}
