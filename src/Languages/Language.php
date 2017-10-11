<?php

namespace SkoobyBot\Languages;

class Language
{
    private static $instance = null;
    private $data = null;

    const DEFAULT_LANG_FILE = 'langs/en-US.json';

    public static function getInstance($language) {
        if (null === self::$instance) {
            if (!$language) {
                throw new Exception('Language is not defined!');
            }

            try {
                self::$instance = new self($language);
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return self::$instance;
    }

    public function getData() {
        return $this->data;
    }

    public function get($name, $params = array()) {
        if (!$name) {
            throw new Exception('Language message name is not defined!');
        }

        if (!isset($this->data[$name])) {
            throw new Exception('Language message text is not found!');
        }

        $text = $this->data[$name];

        foreach($params as $param => $value) {
            $text = preg_replace('/\{' . $param . '\}/', $value, $text);
        }

        return $text;
    }

    private function __construct($language) {
        $file = 'langs/' . $language . '.json';

        if (!file_exists($file)) {
            if (!file_exists(self::DEFAULT_LANG_FILE)) {
                throw new Exception('Default language file is not found!');
            }

            $file = self::DEFAULT_LANG_FILE;
        }

        $json = file_get_contents($file);
        $this->data = json_decode($json, true);

        if (empty($this->data)) {
            throw new Exception('Language file parse error occured!');
        }
    }
}
