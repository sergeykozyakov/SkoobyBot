<?php
namespace SkoobyBot;

class Config
{
    const telegramToken = '401235231:AAFQrgrtdc1MrMbhjs4f09Ug364NjvpfiPM';
    const vkToken = '6126811a6126811a6126811a45617814d1661266126811a38ed88ab48a8e8521cecb98b';

    public static function getTelegramToken() {
        return Config::telegramToken;
    }

    public static function getVkToken() {
        return Config::vkToken;
    }
}