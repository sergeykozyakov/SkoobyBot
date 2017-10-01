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

        $rows = array();
        if (!$this->getIsCron()) {
            try {
                $rows[] = $this->getUser()->getUser($this->getChatId());
            } catch (\Exception $e) {
                $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') Cannot get user(s) from database (' . $e->getMessage() . ')');

                $response = 'Не могу получить информацию о тебе! Попробуй позже.';
                $this->sendMessage($response);
                return;
            }
        }
        else {
            try {
                $rows = $this->getUser()->getAllUsers();
            } catch (\Exception $e) {
                $this->getLogger()->warning('(cron) Cannot get user list from database (' . $e->getMessage() . ')');
                return;
            }
        }

        foreach($rows as $row) {
            if (!$this->readWall($row, $vkAppId, $vkSecret, $vkToken)) {
                break;
            }
        }
    }

    private function readWall($row, $vkAppId, $vkSecret, $vkToken) {
        if (!$row || !isset($row['vk_wall']) || !isset($row['channel']) || !isset($row['vk_last_unixtime'])) {
            if (!$this->getIsCron()) {
                $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') No user information was specified!');

                $response = 'Не могу получить информацию о твоих привязках к VK!';
                $this->sendMessage($response);
            }
            else {
                $this->getLogger()->warning(
                    '(cron, channel: ' . $row['channel'] . ', vk_wall: ' . $row['vk_wall'] . ') No user information was specified!'
                );
            }
            return true;
        }

        $vkWall = $row['vk_wall'];
        $channel = $row['channel'];
        $vkLastUnixTime = $row['vk_last_unixtime'];
        $originalChatId = $this->getChatId();

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
                        '(chat_id: ' . $this->getChatId() . ', vk_wall: ' . $vkWall . ') Cannot read received VK API response!'
                    );

                    $response = 'Не могу получить посты из VK! ' .
                        'Такое бывает, если у пользователя закрыта стена или удалена страница, попробуй потом ещё раз.';
                    $this->sendMessage($response);
                }
                else {
                    $this->getLogger()->warning('(cron, vk_wall: ' . $vkWall . ') Cannot read received VK API response!');
                }
                return true;
            }

            foreach ($posts['response']['items'] as $post) {
                if ($post['post_type'] != 'post' || isset($post['copy_history'])) continue;

                if ($this->getIsCron()) {
                    if ($post['date'] < $vkLastUnixTime) break 2;
                }

                $postId = $post['id'];
                $ownerId = $post['owner_id'];
                $date = $post['date'];
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
                    'date' => $date,
                    'text' => $postText,
                    'photos' => $postPhotos,
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
            $this->sendMessage($response);

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

                if (!$item['text'] && count($item['photos']) == 0 || $item['needLink']) {
                    $isGroup = intval($item['ownerId']) < 0;
                    $ownerAbsId = abs(intval($item['ownerId']));
                    $ownerUrl = $domain ? $domain : (($isGroup ? 'club' : 'id') . $ownerAbsId);

                    $fullUrl = 'https://vk.com/' . $ownerUrl . '?w=wall' . $item['ownerId'] . '_' . $item['id'];
                    $link = '<a href="' . $fullUrl . '">' . $fullUrl . '</a>';

                    $this->sendMessage($link, 'HTML', true);
                }

                if ($this->getIsCron()) {
                    try {
                        $this->getUser()->setVkLastUnixtime($originalChatId, $date);
                    } catch (\Exception $e) {
                        $this->getLogger()->warning(
                            '(cron, chat_id: ' . $originalChatId . ') Cannot set user vk_last_unixtime to database (' . $e->getMessage() . ')'
                        );
                    }
                }
            } catch (\Exception $e) {
                if (!$this->getIsCron()) {
                    throw new \Exception($e->getMessage());
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
