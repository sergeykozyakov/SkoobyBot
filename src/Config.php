<?php
namespace SkoobyBot;

class Config
{
    const ENV_FILE = '../env.json';
    const LOG_DIR = '../logs';

    private static $env = null;

    public static function init() {
        if (file_exists(self::ENV_FILE)) {
            $json = file_get_contents(self::ENV_FILE);
            self::$env = json_decode($json, true);
        }
    }

    public static function getLogDir() {
        return self::LOG_DIR;
    }

    public static function getDbString() {
        return self::get('CLEARDB_DATABASE_URL');
    }

    public static function getTelegramToken() {
        return self::get('TELEGRAM_TOKEN');
    }

    public static function getVkAppId() {
        return self::get('VK_APP_ID');
    }

    public static function getVkSecret() {
        return self::get('VK_SECRET');
    }

    public static function getVkToken() {
        return self::get('VK_TOKEN');
    }

    private static function get($name) {
        if (!$name) return null;

        if (self::$env && isset(self::$env[$name])) {
            return self::$env[$name];
        }
        return getenv($name);
    }
}