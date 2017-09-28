<?php
namespace SkoobyBot;

class Config
{
    const LOG_DIR = '../logs';
    const TELEGRAM_TOKEN = '401235231:AAFQrgrtdc1MrMbhjs4f09Ug364NjvpfiPM';
    const VK_TOKEN = '6126811a6126811a6126811a45617814d1661266126811a38ed88ab48a8e8521cecb98b';

    public static function getLogDir() {
        return self::LOG_DIR;
    }
    
    public static function getTelegramToken() {
        return self::TELEGRAM_TOKEN;
    }

    public static function getVkToken() {
        return self::VK_TOKEN;
    }
}