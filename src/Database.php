<?php
namespace SkoobyBot;

use SkoobyBot\Config;

class Database
{
    private $db = null;
    private static $instance = null;

    public static function getInstance() {
        if (null === self::$instance) {
            try {
                self::$instance = new self();
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return self::$instance;
    }

    public function getDb() {
        return $this->db;
    }

    public function init() {
        $sql = 'CREATE TABLE IF NOT EXISTS users (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            chat_id BIGINT UNSIGNED,
            bot_state VARCHAR(1024),
            vk_wall VARCHAR(1024),
            vk_last_unixtime BIGINT,
            channel VARCHAR(1024),
            KEY chat_id (chat_id),
            KEY connected (vk_wall, vk_last_unixtime, channel)
        );
        CREATE TABLE IF NOT EXISTS posts (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            channel VARCHAR(1024),
            message_id BIGINT UNSIGNED,
            KEY post (channel, message_id)
        );
        CREATE TABLE IF NOT EXISTS likes (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            post_id BIGINT UNSIGNED,
            t_user_id BIGINT UNSIGNED,
            is_liked TINYINT UNSIGNED,
            is_disliked TINYINT UNSIGNED,
            KEY t_user_id (t_user_id)
        );';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        } catch (\Exception $e) {
            throw new \Exception('Database table init error! (' . $e->getMessage() . ')');
        }
    }

    public function addUser($chatId) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $getUser = null;

        try {
            $getUser = $this->getUser($chatId);
        } catch (\Exception $e) {
            throw $e;
        }

        if (!empty($getUser)) {
            return;
        }

        $sql = 'INSERT INTO users (id, chat_id, bot_state, vk_wall, vk_last_unixtime, channel)
            VALUES (NULL, ?, ?, ?, ?, ?)';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$chatId, 'default', '', time(), '']);
        } catch (\Exception $e) {
            throw new \Exception('Cannot add user to database! (' . $e->getMessage() . ')');
        }
    }

    public function addPost($channel, $messageId) {
        if (!$channel) {
            throw new \Exception('channel is not defined!');
        }

        if (!$messageId) {
            throw new \Exception('message_id is not defined!');
        }

        $sql = 'INSERT INTO posts (id, channel, message_id) VALUES (NULL, ?, ?)';

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$channel, $messageId]);
        } catch (\Exception $e) {
            throw new \Exception('Cannot add post to database! (' . $e->getMessage() . ')');
        }
    }

    public function setLike($channel, $messageId, $tUserId, $isLike = true, $isDislike = false) {
        if (!$channel) {
            throw new \Exception('channel is not defined!');
        }

        if (!$messageId) {
            throw new \Exception('message_id is not defined!');
        }

        if (!$tUserId) {
            throw new \Exception('t_user_id is not defined!');
        }

        $getLike = null;
        $retCode = null;

        try {
            $getLike = $this->getLike($channel, $messageId, $tUserId);
        } catch (\Exception $e) {
            throw $e;
        }

        if (empty($getLike)) {
            $sql = 'INSERT INTO likes (id, post_id, t_user_id, is_liked, is_disliked) VALUES ' .
                '(NULL, (SELECT id FROM posts WHERE channel = ? AND message_id = ?), ?, ?, ?)';

            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$channel, $messageId, $tUserId, $isLike ? 1 : 0, $isDislike ? 1 : 0]);

                $retCode = $isLike ? '+like' : ($isDislike ? '+dislike' : null);
            } catch (\Exception $e) {
                throw new \Exception('Cannot add like to database! (' . $e->getMessage() . ')');
            }
        }
        else {
            $likeId = $getLike['like_id'];

            $sql = 'UPDATE likes SET is_liked = IF(is_liked = 1, 0, ?), ' .
                'is_disliked = IF(is_disliked = 1, 0, ?) WHERE id = ?';

            try {
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$isLike ? 1 : 0, $isDislike ? 1 : 0, $likeId]);

                $retCode = $isLike
                    ? $getLike['is_liked'] == 1 ? '-like' : '+like'
                    : ($isDislike
                        ? $getLike['is_disliked'] == 1 ? '-dislike' : '+dislike'
                        : null);
            } catch (\Exception $e) {
                throw new \Exception('Cannot add like to database! (' . $e->getMessage() . ')');
            }
        }

        return $retCode;
    }

    public function getUser($chatId) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $sql = 'SELECT chat_id, bot_state, vk_wall, vk_last_unixtime, channel FROM users WHERE chat_id = ?';
        $result = null;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$chatId]);
            $result = $stmt->fetch();
        } catch (\Exception $e) {
            throw new \Exception('Cannot get user from database! (' . $e->getMessage() . ')');
        }

        return $result;
    }

    public function getLike($channel, $messageId, $tUserId) {
        if (!$channel) {
            throw new \Exception('channel is not defined!');
        }

        if (!$messageId) {
            throw new \Exception('message_id is not defined!');
        }

        if (!$tUserId) {
            throw new \Exception('t_user_id is not defined!');
        }

        $sql = 'SELECT likes.id AS like_id, is_liked, is_disliked FROM likes ' .
            'LEFT JOIN posts ON posts.id = likes.post_id ' .
            'WHERE posts.channel = ? AND posts.message_id = ? AND likes.t_user_id = ?';

        $result = null;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$channel, $messageId, $tUserId]);
            $result = $stmt->fetch();
        } catch (\Exception $e) {
            throw new \Exception('Cannot get like from database! (' . $e->getMessage() . ')');
        }

        return $result;
    }

    public function getLikesCount($channel, $messageId) {
        if (!$channel) {
            throw new \Exception('channel is not defined!');
        }

        if (!$messageId) {
            throw new \Exception('message_id is not defined!');
        }

        $sql = 'SELECT IF(SUM(is_liked) IS NOT NULL, SUM(is_liked), 0) AS sum_likes, ' .
            'IF(SUM(is_disliked) IS NOT NULL, SUM(is_disliked), 0) AS sum_dislikes FROM likes ' .
            'LEFT JOIN posts ON posts.id = likes.post_id ' .
            'WHERE posts.channel = ? AND posts.message_id = ?';

        $result = null;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$channel, $messageId]);
            $result = $stmt->fetch();
        } catch (\Exception $e) {
            throw new \Exception('Cannot get likes count from database! (' . $e->getMessage() . ')');
        }

        return $result;
    }

    public function getAllConnectedUsers() {
        $sql = "SELECT chat_id, bot_state, vk_wall, vk_last_unixtime, channel FROM users 
            WHERE vk_wall != '' AND vk_last_unixtime > 0 AND channel != ''";
        $result = null;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $result = $stmt->fetchAll();
        } catch (\Exception $e) {
            throw new \Exception('Cannot get user list from database! (' . $e->getMessage() . ')');
        }

        return $result;
    }

    public function getIsChannelConnected($chatId, $channel) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        if (!$channel) {
            throw new \Exception('channel is not defined!');
        }

        $sql = "SELECT COUNT(*) AS count FROM users WHERE
            vk_wall != '' AND vk_last_unixtime > 0 AND channel = ? AND chat_id != ?";
        $result = null;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$channel, $chatId]);
            $result = $stmt->fetch();
        } catch (\Exception $e) {
            throw new \Exception('Cannot get if channel is connected from database! (' . $e->getMessage() . ')');
        }

        return $result;
    }

    public function setBotState($chatId, $state = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = ['bot_state' => $state];

        try {
            $this->set($chatId, $arr);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setVkWall($chatId, $wallId = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = [
            'vk_wall' => $wallId,
            'vk_last_unixtime' => time()
        ];

        try {
            $this->set($chatId, $arr);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setVkLastUnixtime($chatId, $vkLastUnixTime = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = ['vk_last_unixtime' => $vkLastUnixTime];

        try {
            $this->set($chatId, $arr);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setChannel($chatId, $channel = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = [
            'channel' => $channel,
            'vk_last_unixtime' => time()
        ];

        try {
            $this->set($chatId, $arr);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delVkConnection($chatId) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = [
            'vk_wall' => '',
            'vk_last_unixtime' => time(),
            'channel' => ''
        ];

        try {
            $this->set($chatId, $arr);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function set($chatId, $params) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        if (empty($params)) {
            throw new \Exception('User params is not defined!');
        }

        $sql = 'UPDATE users SET ';
        $namesList = [];
        $valuesList = [];

        foreach($params as $field => $param) {
            if (!preg_match('/^[a-zA-Z0-9$_]+$/', $field)) {
                throw new \Exception('User field name is forbidden!');
            }

            $namesList[] = $field . ' = ?';
            $valuesList[] = $param;
        }

        $sql .= join(', ', $namesList) . ' WHERE chat_id = ?';
        $valuesList[] = $chatId;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($valuesList);
        } catch (\Exception $e) {
            throw new \Exception('Cannot set user ' . $field . ' to database!  (' . $e->getMessage() . ')');
        }
    }

    private function __clone() {}

    private function __construct() {
        $dbString = Config::getDbString();

        if (!$dbString) {
            throw new \Exception('No DB connection string was specified!');
        }

        $regExp = '/^.+:\/\/(.+):(.+)@(.+)\/(.+)\?.+$/';

        $pdoString = preg_replace($regExp, 'mysql:host=\3;dbname=\4;charset=utf8', $dbString);
        $username = preg_replace($regExp, '\1', $dbString);
        $password = preg_replace($regExp, '\2', $dbString);

        try {
            $db = new \PDO($pdoString, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            $this->db = $db;
        } catch (\Exception $e) {
            throw new \Exception('Database connection failed! (' . $e->getMessage() . ')');
        }
    }
}
