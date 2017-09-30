<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Actions\BaseAction;
use SkoobyBot\Commands\GetVkCommand;

class Sender extends BaseAction
{
    public function start() {
        if (!$this->getApi()) {
            $this->getLogger()->error('Cannot start sending messages by cron until Telegram API is connected!');
            throw new \Exception('[ERROR] Cannot start sending messages by cron until Telegram API is connected!');
        }

        try {
            $getVkCommand = new GetVkCommand($this->getApi(), $this->getLogger());
            $getVkCommand
                ->setIsCron(true)
                ->start();
        } catch (\Exception $e) {
            $this->getLogger()->error('Cannot execute cron VK API posts import: ' . $e->getMessage());
            throw new \Exception('[ERROR] Cannot execute cron VK API posts import: ' . $e->getMessage());
        }
    }
}
