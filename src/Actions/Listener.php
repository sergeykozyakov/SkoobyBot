<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Actions\BaseAction;

use SkoobyBot\Commands\StartCommand;
use SkoobyBot\Commands\HelpCommand;
use SkoobyBot\Commands\SetVkCommand;
use SkoobyBot\Commands\GetVkCommand;
use SkoobyBot\Commands\BaseCommand;

class Listener extends BaseAction
{
    public function start() {
        $result = $this->getApi()->getWebhookUpdates();

        if (!$result || !$result->getMessage()) {
            $this->getLogger()->error('Cannot read received Telegram API message!');
            throw new \Exception('[ERROR] Cannot read received Telegram API message!');
        }

        $text = $result->getMessage()->getText();
        $chatId = $result->getMessage()->getChat()->getId();

        switch ($text) {
            case '/start':
                $keyboard = [["\xE2\x9E\xA1 Помощь"], ["\xE2\x9E\xA1 Последний пост VK"]];
                $replyMarkup = $this->getApi()->replyKeyboardMarkup([
                    'keyboard' => $keyboard,
                    'resize_keyboard' => true,
                    'one_time_keyboard' => false
                ]);

                try {
                    $startCommand = new StartCommand($this->getApi(), $this->getLogger());
                    $startCommand
                        ->setMessage($result->getMessage())
                        ->setReplyMarkup($replyMarkup)
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error(
                        '(chat_id: ' . $chatId . ') Cannot execute bot /start command: ' . $e->getMessage()
                    );
                    throw new \Exception('[ERROR] Cannot execute bot /start command: ' . $e->getMessage());
                }
                break;
            case '/help':
            case "\xE2\x9E\xA1 Помощь":
                try {
                    $helpCommand = new HelpCommand($this->getApi(), $this->getLogger());
                    $helpCommand
                        ->setMessage($result->getMessage())
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error(
                        '(chat_id: ' . $chatId . ') Cannot execute bot /help command: ' . $e->getMessage()
                    );
                    throw new \Exception('[ERROR] Cannot execute bot /help command: ' . $e->getMessage());
                }
                break;
            case '/setVk':
            case "\xE2\x9E\xA1 Добавить привязку к VK":
                try {
                    $setVkCommand = new SetVkCommand($this->getApi(), $this->getLogger());
                    $setVkCommand
                        ->setMessage($result->getMessage())
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error(
                        '(chat_id: ' . $chatId . ') Cannot execute bot /setVk command: ' . $e->getMessage()
                    );
                    throw new \Exception('[ERROR] Cannot execute bot /setVk command: ' . $e->getMessage());
                }
                break;
            case '/getVk':
            case "\xE2\x9E\xA1 Последний пост VK":
                try {
                    $getVkCommand = new GetVkCommand($this->getApi(), $this->getLogger());
                    $getVkCommand
                        ->setMessage($result->getMessage())
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error(
                        '(chat_id: ' . $chatId . ') Cannot execute bot /getVk command: ' . $e->getMessage()
                    );
                    throw new \Exception('[ERROR] Cannot execute bot /getVk command: ' . $e->getMessage());
                }
                break;
            default:
                try {
                    $defaultCommand = new BaseCommand($this->getApi(), $this->getLogger());
                    $defaultCommand
                        ->setMessage($result->getMessage())
                        ->start();
                } catch (\Exception $e) {
                    $this->getLogger()->error(
                        '(chat_id: ' . $chatId . ') Cannot execute bot default command: ' . $e->getMessage()
                    );
                    throw new \Exception('[ERROR] Cannot execute bot default command: ' . $e->getMessage());
                }
                break;
        }
    }
}
