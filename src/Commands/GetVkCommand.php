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

        if (!$this->getIsCron()) {
            try {
                $this->getDatabase()->setBotState($this->getChatId(), 'default');
            } catch (\Exception $e) {
                throw $e;
            }
        }

        $vkAppId = Config::getVkAppId();
        $vkSecret = Config::getVkSecret();
        $vkToken = Config::getVkToken();

        if (!$vkAppId || !$vkSecret || !$vkToken) {
            if (!$this->getIsCron()) {
                $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') No VK API tokens were specified!');

                try {
                    $response = $this->getLanguage()->get('get_vk_command_no_tokens');
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
                $user = $this->getDatabase()->getUser($this->getChatId());
                $rows[] = $user;
            } catch (\Exception $e) {
                throw $e;
            }
        }
        else {
            try {
                $rows = $this->getDatabase()->getAllConnectedUsers();
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
        if (!isset($row['vk_wall']) || !$row['vk_wall'] ||
            !isset($row['vk_last_unixtime']) || !$row['vk_last_unixtime'] ||
            !isset($row['channel']) || !$row['channel']) {
            if (!$this->getIsCron()) {
                $this->getLogger()->warning(
                    '(chat_id: ' . $this->getChatId() . ') No user VK import information was specified!'
                );

                try {
                    $response = $this->getLanguage()->get('get_vk_command_no_import_set');
                    $this->sendMessage($response);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
            return true;
        }
        else {
            if (!$this->getIsCron()) {
                try {
                    $response = $this->getLanguage()->get('get_vk_command_import_set', array(
                        'vk_wall' => $row['vk_wall'],
                        'channel' => $row['channel']
                    ));

                    $this->sendMessage($response);
                } catch (\Exception $e) {
                    throw $e;
                }
            }
        }

        $vkWall = $row['vk_wall'];
        $channel = $row['channel'];
        $vkLastUnixTime = $row['vk_last_unixtime'];
        $originalChatId = $row['chat_id'];

        if ($this->getIsCron()) {
            $this->setChatId($channel);
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

                    try {
                        $response = $this->getLanguage()->get('get_vk_command_server_error');
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
                    
                    try {
                        $response = $this->getLanguage()->get('get_vk_command_wall_error');
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
                                $attachmentText = preg_replace('/\[(.+?(?=\|))\|(.+?(?=\]))\]/', '\2', $attachment['photo']['text']);
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
                                break;
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
            $this->getLogger()->warning('(chat_id: ' . $this->getChatId() . ') No user VK posts were found!');

            try {
                $response = $this->getLanguage()->get('get_vk_command_wall_empty');
                $this->sendMessage($response);
            } catch (\Exception $e) {
                throw $e;
            }
            return true;
        }

        foreach (array_reverse($postList) as $item) {
            try {
                if ($item['text'] && strlen($item['text']) <= 200 && count($item['photos']) == 1 && !$item['photos'][0]['text']) {
                    $item['photos'][0]['text'] = $item['text'];
                    $item['text'] = '';
                }

                if ($item['text']) {
                    $this->sendMessage($item['text'], null, true);
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
                    $this->getDatabase()->setVkLastUnixtime($originalChatId, $item['date']);
                }
            } catch (\Exception $e) {
                if (!$this->getIsCron()) {
                    throw $e;
                }
                else {
                    $this->getLogger()->warning('(cron, channel: ' . $this->getChatId() . ') ' . $e->getMessage());
                }
                return true;
            }
        }
        return true;
    }
}
