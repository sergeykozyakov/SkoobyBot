<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Actions\BaseAction;
use SkoobyBot\Commands\GetVkCommand;

class Sender extends BaseAction
{
    public function start() {
        $inlineMarkup = $this->getApi()->replyKeyboardMarkup([
            'inline_keyboard' => [[
                ['text' => "\xF0\x9F\x91\x8D", 'callback_data' => 'like'],
                ['text' => "\xF0\x9F\x91\x8E", 'callback_data' => 'dislike']
            ]]
        ]);

        try {
            $getVkCommand = new GetVkCommand($this->getApi(), $this->getLogger());
            $getVkCommand
                ->setIsCron(true)
                ->setReplyMarkup($inlineMarkup)
                ->start();
        } catch (\Exception $e) {
            $this->getLogger()->error('(cron) Cannot execute VK API posts import: ' . $e->getMessage());
            throw new \Exception('[ERROR] Cannot execute VK API posts import: ' . $e->getMessage());
        }
    }
}
