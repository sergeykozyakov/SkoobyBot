<?php
namespace SkoobyBot\Databases;

use SkoobyBot\Config;

class User
{
    protected $db = null;
    protected $logger = null;

    private static $instance = null;

    public static function getInstance($logger = null) {
        if (null === self::$instance) {
            self::$instance = new self($logger);
        }

        if ($logger) {
            self::$instance->logger = $logger;
        }
        return self::$instance;
    }

    public function getDb() {
        return $this->db;
    }

    public function getLogger() {
        return $this->logger;
    }

    public function init() {
        $sql = 'CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            chat_id VARCHAR(512),
            bot_state VARCHAR(512),
            vk_wall VARCHAR(512),
            vk_last_unixtime VARCHAR(512),
            channel VARCHAR(512)
        )';

        try {
            $stmt = $this->getDb()->prepare($sql);
            $stmt->execute();
        } catch (\Exception $e) {
            $this->getLogger()->error('[ERROR] Database table init error: ' . $e->getMessage());
            throw new \Exception('[ERROR] Database table init error: ' . $e->getMessage());
        }
    }

    public function addUser($chatId) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        if (!empty($this->getUser($chatId))) {
            return;
        }

        $sql = 'INSERT INTO users (id, chat_id, bot_state, vk_wall, vk_last_unixtime, channel)
            VALUES (NULL, ?, ?, ?, ?, ?)';

        $stmt = $this->getDb()->prepare($sql);
        
        if ($chatId == '367995212') { // TODO: временная заглушка для меня
            $stmt->execute(array($chatId, '', 'sergeykozyakov', '1500394060', '@sergeykozyakov_live'));
            return;
        }
        $stmt->execute(array($chatId, '', '', time(), ''));
    }

    public function getUser($chatId) {
        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $sql = 'SELECT chat_id, bot_state, vk_wall, vk_last_unixtime, channel FROM users WHERE chat_id = ?';

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute(array($chatId));

        return $stmt->fetch();
    }

    public function getAllUsers() {
        $sql = 'SELECT chat_id, bot_state, vk_wall, vk_last_unixtime, channel FROM users';

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function setBotState($chatId, $state) {
        $this->set('bot_state', $vkLastUnixTime, $chatId);
    }

    public function setVkWall($chatId, $wallId) {
        $this->set('vk_wall', $vkLastUnixTime, $chatId);
    }

    public function setVkLastUnixtime($chatId, $vkLastUnixTime) {
        $this->set('vk_last_unixtime', $vkLastUnixTime, $chatId);
    }

    public function setChannel($chatId, $channel) {
        $this->set('channel', $channel, $chatId);
    }

    protected function set($field, $param, $chatId) {
        if (!$field || !$param) {
            throw new \Exception('User field or value is not defined!');
        }

        if (!$chatId) {
            throw new \Exception('chat_id is not defined!');
        }

        $sql = 'UPDATE users SET ' . $field . ' = ? WHERE chat_id = ?';

        $stmt = $this->getDb()->prepare($sql);
        $stmt->execute(array($param, $chatId));
    }

    private function __clone() {}

    private function __construct($logger) {
        if (!$logger) {
            throw new \Exception('[ERROR] Logger component is not defined!');
        }

        $this->logger = $logger;
        $dbString = Config::getDbString();

        if (!$dbString) {
            $this->getLogger()->error('No DB connection string was specified!');
            throw new \Exception('[ERROR] No DB connection string was specified!');
        }

        $regExp = '/^.+:\/\/(.+):(.+)@(.+)\/(.+)\?.+$/';

        $pdoString = preg_replace($regExp, 'mysql:host=\3;dbname=\4', $dbString);
        $username = preg_replace($regExp, '\1', $dbString);
        $password = preg_replace($regExp, '\2', $dbString);

        try {
            $db = new \PDO($pdoString, $username, $password, [
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
            ]);
            $this->db = $db;
        } catch (\Exception $e) {
            $this->getLogger()->error('[ERROR] Database connection failed: ' . $e->getMessage());
            throw new \Exception('[ERROR] Database connection failed: ' . $e->getMessage());
        }
    }
}
