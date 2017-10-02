<?php
namespace SkoobyBot\Commands;

use SkoobyBot\Config;
use SkoobyBot\Commands\BaseCommand;

use VK\VK;
use VK\VKException;

class GetVkCommand extends BaseCommand
{
    private static $limit = 20;
    private static $photoSizes = array(1280, 807, 604, 130, 75);

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

                try {
                    $this->sendMessage($response);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            else {
                $this->getLogger()->warning('(cron) No VK API tokens were specified!');
            }
            return;
        }

        $rows = array();
        if (!$this->getIsCron()) {
            try {
                $rows[] = $this->getDatabase()->getUser($this->getChatId());
            } catch (\Exception $e) {
                $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') ' . $e->getMessage());
                $response = 'Не могу получить информацию о тебе! Попробуй позже.';

                try {
                    $this->sendMessage($response);
                } catch (\Exception $e) {
                    throw $e;
                }
                return;
            }
        }
        else {
            try {
                $rows = $this->getDatabase()->getAllUsers();
            } catch (\Exception $e) {
                throw $e;
            }
        }

        try {
            foreach($rows as $row) {
                if (!$this->readWall($row, $vkAppId, $vkSecret, $vkToken)) {
                    break;
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function readWall($row, $vkAppId, $vkSecret, $vkToken) {
        if (!$row || !isset($row['vk_wall']) || !isset($row['channel']) || !isset($row['vk_last_unixtime'])) {
            if (!$this->getIsCron()) {
                $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') No user information was specified!');
                $response = 'Не могу получить информацию о твоих привязках к VK!';

                try {
                    $this->sendMessage($response);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            return true;
        }

        $vkWall = $row['vk_wall'];
        $channel = $row['channel'];
        $vkLastUnixTime = $row['vk_last_unixtime'];
        $originalChatId = $row['chat_id'];

        if ($this->getIsCron()) {
            $this->chatId = $channel;
        }

        $offset = 1;
        $postList = array();

        $domain = !is_numeric($vkWall) ? $vkWall : null;

        while($offset > 0) {
            $posts = null;

            try {
                $vk = new VK($vkAppId, $vkSecret, $vkToken);
                $posts = $vk->api('wall.get', array(
                    'owner_id' => !$domain ? $vkWall : null,
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

                    try {
                        $this->sendMessage($response);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                else {
                    $this->getLogger()->warning('(cron) VK API connection error! ' . $e->getMessage());
                }
                return false;
            }

            if (!$posts || !isset($posts['response']) || !isset($posts['response']['items'])) {
                if (!$this->getIsCron()) {
                    $this->getLogger()->warning(
                        '(chat_id: ' . $this->getChatId() . ', vk_wall: ' . $vkWall . ') Cannot read received VK API response!'
                    );

                    $response = 'Не могу получить посты из VK! ' .
                        'Такое бывает, если у пользователя закрыта стена или удалена страница, попробуй потом ещё раз.';
                    
                    try {
                        $this->sendMessage($response);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
                else {
                    $this->getLogger()->warning('(cron, vk_wall: ' . $vkWall . ') Cannot read received VK API response!');
                }
                return true;
            }

            foreach ($posts['response']['items'] as $post) {
                if ($post['post_type'] != 'post' || isset($post['copy_history'])) continue;

                $postId = $post['id'];
                $ownerId = $post['owner_id'];
                $date = $post['date'];
                $postText = preg_replace('/\[(.+?(?=\|))\|(.+?(?=\]))\]/', '\2', $post['text']);

                if ($this->getIsCron()) {
                    if ($date <= $vkLastUnixTime) break 2;
                }

                $needLink = false;
                $postPhotos = array();
                $postLinks = array();

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
                            case 'link':
                                $attachmentUrl = $attachment['link']['url'];
                                $postLinks[] = array('url' => $attachmentUrl);
                            default:
                                $needLink = true;
                                break;
                        }
                    }
                }

                $postList[] = array(
                    'id' => $postId,
                    'ownerId' => $ownerId,
                    'date' => $date,
                    'text' => $postText,
                    'photos' => $postPhotos,
                    'links' => $postLinks,
                    'needLink' => $needLink
                );

                if (!$this->getIsCron()) {
                    break 2;
                }
            }

            $offset += self::$limit;
        }

        if (!$this->getIsCron() && count($postList) == 0) {
            $response = 'Пока нет ни одного поста.';

            try {
                $this->sendMessage($response);
            } catch (\Exception $e) {
                throw $e;
            }
            return true;
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

                if (count($item['links']) > 0) {
                    foreach($item['links'] as $postLink) {
                        $this->sendMessage($postLink['url'], null, false);
                    }
                }

                if (!$item['text'] && count($item['photos']) == 0 && count($item['links']) == 0 || $item['needLink']) {
                    $isGroup = intval($item['ownerId']) < 0;
                    $ownerAbsId = abs(intval($item['ownerId']));
                    $ownerUrl = $domain ? $domain : (($isGroup ? 'club' : 'id') . $ownerAbsId);

                    $link = 'https://vk.com/' . $ownerUrl . '?w=wall' . $item['ownerId'] . '_' . $item['id'];
                    $this->sendMessage($link, null, true);
                }

                if ($this->getIsCron()) {
                    try {
                        $this->getDatabase()->setVkLastUnixtime($originalChatId, $item['date']);
                    } catch (\Exception $e) {
                        $this->getLogger()->warning('(cron, chat_id: ' . $originalChatId . ') ' . $e->getMessage());
                    }
                }
            } catch (\Exception $e) {
                if (!$this->getIsCron()) {
                    throw $e;
                }
                else {
                    $this->getLogger()->warning(
                        '(cron, channel: ' . $this->getChatId() . ') Cannot send photo/message to channel via Telegram API!'
                    );
                }
                return true;
            }
        }
        return true;
    }
}
