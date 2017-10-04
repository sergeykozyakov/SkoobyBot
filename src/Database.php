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
        )';

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
            $stmt->execute(array($chatId, 'default', '', time(), ''));
        } catch (\Exception $e) {
            throw new \Exception('Cannot add user to database! (' . $e->getMessage() . ')');
        }
    }

    public function getUser($chatId) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $sql = 'SELECT chat_id, bot_state, vk_wall, vk_last_unixtime, channel FROM users WHERE chat_id = ?';
        $result = null;

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute(array($chatId));
            $result = $stmt->fetch();
        } catch (\Exception $e) {
            throw new \Exception('Cannot get user from database! (' . $e->getMessage() . ')');
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

    public function setBotState($chatId, $state = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = array('bot_state' => $state);

        try {
            $this->set($arr, $chatId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setVkWall($chatId, $wallId = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = array(
            'vk_wall' => $wallId,
            'vk_last_unixtime' => time()
        );

        try {
            $this->set($arr, $chatId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setVkLastUnixtime($chatId, $vkLastUnixTime = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = array('vk_last_unixtime' => $vkLastUnixTime);

        try {
            $this->set($arr, $chatId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function setChannel($chatId, $channel = '') {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = array(
            'channel' => $channel,
            'vk_last_unixtime' => time()
        );

        try {
            $this->set($arr, $chatId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delVkConnection($chatId) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $arr = array(
            'vk_wall' => '',
            'vk_last_unixtime' => time(),
            'channel' => ''
        );

        try {
            $this->set($arr, $chatId);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function set($params, $chatId) {
        if (empty($params)) {
            throw new \Exception('User params is not defined!');
        }

        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $sql = 'UPDATE users SET ';
        $namesList = array();
        $valuesList = array();

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
