<?php
namespace SkoobyBot\Actions;

use SkoobyBot\Actions\BaseAction;

use Telegram\Bot\Exceptions\TelegramSDKException;

class InlineAction extends BaseAction
{
    private $callbackQuery = null;

    public function start() {
        if (!$this->callbackQuery) {
            throw new \Exception('Telegram API callback_query is null!');
        }

        $query = $this->callbackQuery->getData();
        $queryId = $this->callbackQuery->getId();

        $tUserId = $this->callbackQuery->getFrom()->getId();
        $channel = '@' . $this->callbackQuery->getMessage()->getChat()->getUsername();
        $messageId = $this->callbackQuery->getMessage()->getMessageId();

        if ($query != 'like' && $query != 'dislike') {
            throw new \Exception('Unsupported inline keyboard query via Telegram API! (' . $query . ')');
        }

        $callbackCode = null;
        $likesCountList = array();

        try {
            $callbackCode = $this->getDatabase()->setLike(
                $channel, $messageId, $tUserId, ($query == 'like'), ($query == 'dislike')
            );
            $likesCountList = $this->getDatabase()->getLikesCount($channel, $messageId);
        } catch (\Exception $e) {
            throw $e;
        }

        $sumLikes = 0;
        $sumDislikes = 0;

        if (isset($likesCountList['sum_likes'])) {
            $sumLikes = $likesCountList['sum_likes'];
        }

        if (isset($likesCountList['sum_dislikes'])) {
            $sumDislikes = $likesCountList['sum_dislikes'];
        }

        $replyMarkup = $this->getApi()->replyKeyboardMarkup([
            'inline_keyboard' => [[
                ['text' => "\xF0\x9F\x91\x8D" . ($sumLikes > 0 ? ' ' . $sumLikes : ''), 'callback_data' => 'like'],
                ['text' => "\xF0\x9F\x91\x8E" . ($sumDislikes > 0 ? ' ' . $sumDislikes : ''), 'callback_data' => 'dislike']
            ]]
        ]);

        $callbackText = null;

        switch ($callbackCode) {
            case '+like':
                $callbackText = 'Вам это понравилось';
                break;
            case '-like':
                $callbackText = 'Вы забрали свой лайк';
                break;
            case '+dislike':
                $callbackText = 'Вам это не понравилось';
                break;
            case '-dislike':
                $callbackText = 'Вы забрали свой дизлайк';
                break;
            default:
                $callbackText = 'Произошла ошибка!';
                break;
        }

        try {
            $this->getApi()->editMessageReplyMarkup([
                'chat_id' => $channel,
                'message_id' => $messageId,
                'reply_markup' => $replyMarkup
            ]);

            $this->getApi()->answerCallbackQuery([
                'callback_query_id' => $queryId,
                'text' => $callbackText
            ]);
        } catch (TelegramSDKException $e) {
            throw new \Exception('Cannot edit message inline keyboard via Telegram API! (' . $e->getMessage() . ')');
        }
    }

    public function getCallbackQuery() {
        return $this->callbackQuery;
    }

    public function setCallbackQuery($callbackQuery) {
        $this->callbackQuery = $callbackQuery;
        return $this;
    }
}