<?php
namespace SkoobyBot\Commands;

class CommandFactory {
    public static function get($class, $api, $logger) {
        if (!$class) {
            throw new \Exception('Class name is not defined!');
        }

        if (!$api) {
            throw new \Exception('Telegram API component is not defined!');
        }

        if (!$logger) {
            throw new \Exception('Logger component is not defined!');
        }

        $className = __NAMESPACE__ . '\\' . $class;

        if (!class_exists($className)) {
            throw new \Exception('Command class does not exist!');
        }

        try {
            $object = new $className($api, $logger);
        } catch (\Exception $e) {
            throw $e;
        }

        return $object;
    }
}