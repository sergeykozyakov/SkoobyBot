<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Config;
use SkoobyBot\Commands\BaseCommand;

use VK\VK;
use VK\VKException;

class GetVkCommand extends BaseCommand
{
    protected static $limit = 20;
    protected static $photoSizes = array(1280, 807, 604, 130, 75);

    public function start() {
        if (!$this->getMessage() && !$this->getIsCron()) {
            throw new \Exception('Telegram API message is not defined!');
        }

        $vkAppId = Config::getVkAppId();
        $vkSecret = Config::getVkSecret();
        $vkToken = Config::getVkToken();

        if (!$vkAppId || !$vkSecret || !$vkToken) {
            if (!$this->getIsCron()) {
                $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') No VK API tokens were specified!');

                $response = 'Нет ключей доступа для подключения к серверу VK! Извини, это поломка на моей стороне.';
                $this->sendMessage($response);
            }
            else {
                $this->getLogger()->warning('(cron) No VK API tokens were specified!');
            }
            return;
        }

        // TODO: заглушка - заменить на чтение из БД
        $json = file_get_contents('../367995212.json');
        $row = json_decode($json, true);

        //$rows = array($dbRow);
        //foreach($rows as $row) {
            /*if (!*/ $this->readWall($row['vk_wall'], $vkAppId, $vkSecret, $vkToken, $row['channel'], $row['vk_last_unixtime']); /*) {
                break;
            }*/
        //}
        // TODO: конец заглушки - заменить на чтение из БД
    }

    private function readWall($wallId, $vkAppId, $vkSecret, $vkToken, $channelId, $vkDate) {
        if (!$wallId || !$chatId || !$vkDate || !is_numeric($vkDate)) {
            if (!$this->getIsCron()) {
                $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') No VK API wall_id or Telegram channel were specified!');

                $response = 'Не указана стена VK или Telegram канал для импорта!';
                $this->sendMessage($response);
            }
            else {
                $this->getLogger()->warning(
                    '(cron, chat_id: ' . $chatId . ', vk_wall: ' . $wallId . ') No VK API wall_id or Telegram channel were specified!'
                );
            }
            return true;
        }

        if ($this->getIsCron()) {
            $this->chatId = $channelId;
        }

        $offset = 1;
        $postList = array();

        $domain = !is_numeric($wallId) ? $wallId : null;

        while($offset > -1) {
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
                if (!$this->getIsCron()) {
                    $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') VK API connection error! ' . $e->getMessage());

                    $response = 'Не могу подключиться к серверу VK! Попробуй позже.';
                    $this->sendMessage($response);
                }
                else {
                    $this->getLogger()->warning('(cron) VK API connection error! ' . $e->getMessage());
                }
                return false;
            }

            if (!$posts || !isset($posts['response']) || !isset($posts['response']['items'])) {
                if (!$this->getIsCron()) {
                    $this->getLogger()->warning(
                        '(chat_id: ' . $this->getChatId() . ', vk_wall: ' . $wallId . ') Cannot read received VK API response!'
                    );

                    $response = 'Не могу получить посты из VK! ' .
                        'Такое бывает, если у пользователя закрыта стена или удалена страница, попробуй потом ещё раз.';
                    $this->sendMessage($response);
                }
                else {
                    $this->getLogger()->warning('(cron, vk_wall: ' . $wallId . ') Cannot read received VK API response!');
                }
                return true;
            }

            foreach ($posts['response']['items'] as $post) {
                if ($post['date'] < $vkDate) break;
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

                if (!$item['text'] && count($item['photos']) == 0 || $item['needLink']) {
                    $isGroup = intval($item['ownerId']) < 0;
                    $ownerAbsId = abs(intval($item['ownerId']));
                    $ownerUrl = $domain ? $domain : (($isGroup ? 'club' : 'id') . $ownerAbsId);

                    $fullUrl = 'https://vk.com/' . $ownerUrl . '?w=wall' . $item['ownerId'] . '_' . $item['id'];
                    $link = '<a href="' . $fullUrl . '">' . $fullUrl . '</a>';

                    $this->sendMessage($link, 'HTML', true);
                }
            } catch (\Exception $e) {
                if (!$this->getIsCron()) {
                    $this->getLogger()->warning(
                        '(chat_id: ' . $this->getChatId() . ') Cannot send photo/message to channel via Telegram API!'
                    );

                    $response = 'Не могу отправить пост в канал Telegram! ' .
                        'Такое бывает, если у Skooby Bot нет прав на запись в канал или канал указан неверно.';
                    $this->sendMessage($response);
                }
                else {
                    $this->getLogger()->warning(
                        '(cron, channel: ' . $this->getChatId() . ') Cannot send photo/message to channel via Telegram API!'
                    );
                }
                return true;
            }
        }
        // TODO: записать в БД для данного chat_id дату самого свежего поста!
        return true;
    }
}
