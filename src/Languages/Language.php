<?php
namespace SkoobyBot\Languages;

class Language
{
    private static $instance = null;

    private $data = null;
    private $language = null;

    const DEFAULT_LANG = 'en-US';
    const DEFAULT_LANG_FILE = __DIR__ . '/langs/en-US.json';

    public static function getInstance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function setLanguage($language) {
        $this->language = $language;
        return $this;
    }

    public function getLanguage() {
        return $this->language;
    }

    public function getData() {
        return $this->data;
    }

    public function init() {
        $isDefault = false;

        if (!$this->language) {
            $this->language = self::DEFAULT_LANG;
            $isDefault = true;
        }
        else if (stripos($this->language, 'ru') !== false) {
            $this->language = 'ru-RU';
        }

        if ($this->language == self::DEFAULT_LANG) {
            $isDefault = true;
        }

        $file = __DIR__ . '/langs/' . $this->language . '.json';

        if (!file_exists($file)) {
            if (!file_exists(self::DEFAULT_LANG_FILE)) {
                throw new \Exception('Language default file is not found!');
            }

            $file = self::DEFAULT_LANG_FILE;
            $isDefault = true;
        }

        $dataDefault = [];

        if (!$isDefault) {
            if (!file_exists(self::DEFAULT_LANG_FILE)) {
                throw new \Exception('Language default file is not found!');
            }

            $json = file_get_contents(self::DEFAULT_LANG_FILE);
            $dataDefault = json_decode($json, true);
            
            if (empty($dataDefault)) {
                throw new \Exception('Language default file parse error occured!');
            }
        }

        $json = file_get_contents($file);
        $dataCustom = json_decode($json, true);

        if (empty($dataCustom)) {
            throw new \Exception('Language custom file parse error occured!');
        }

        $this->data = array_merge($dataDefault, $dataCustom);
    }

    public function get($name, $params = []) {
        if (!$this->language) {
            throw new \Exception('Language is not defined!');
        }

        if (!$this->data) {
            throw new \Exception('Language data is not defined!');
        }

        if (!$name) {
            throw new \Exception('Language message name is not defined!');
        }

        if (!isset($this->data[$name])) {
            throw new \Exception('Language message text is not found!');
        }

        $text = $this->data[$name];

        foreach($params as $param => $value) {
            $text = str_replace('{' . $param . '}', $value, $text);
        }

        return $text;
    }

    private function __construct() {}
}
