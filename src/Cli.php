<?php
include('../vendor/autoload.php');

use SkoobyBot\App;

class Cli
{
    public static function getParams() {
        $params = [];

        if (isset($_SERVER['argv']) && isset($_SERVER['argc']) && $_SERVER['argc'] > 1) {
            $params = $_SERVER['argv'];
            array_splice($params, 0, 1);
        }

        return $params;
    }
}

$app = App::getInstance();
$app->start(Cli::getParams());
