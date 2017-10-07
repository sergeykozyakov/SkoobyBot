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
        $chatId = $this->callbackQuery->getMessage()->getChat()->getId();
        $messageId = $this->callbackQuery->getMessage()->getMessageId();

        $date = null;
        $replyMarkup = null;
        $callbackText = '';

        switch ($query) {
            case 'like':
                /** TODO:
                 * При создании сообщения в канал - делать запись в БД likes, узнав message_id.
                 * Это делается через return on sendMessage(), поле getMessageId().
                 * 
                 * Здесь проверять таблицу по юзеру и чату+сообщению
                 * Если лайк пустой, то сообщение, что понравилось, затем запись в бд и подсчёт
                 * и выдача лайков этого чата+сообщения
                 * 
                 * Если лайк = 1, то сообщение, что лайк отозван, затем запись в бд и подсчёт
                 * и выдача лайков этого чата+сообщения
                 * 
                 * Настроить связь с БД таблицей likes:
                 * id = A_I,
                 * channel = $this->callbackQuery->getMessage()->getChat()->getId(),
                 * message_id = $this->callbackQuery->getMessage()->getMessageId(),
                 * user_id = $this->callbackQuery->getFrom()->getId(),
                 * is_liked = 1/0,
                 * is_disliked = 1/0
                 */
                $date = date("d.m.Y H:i:s");
                $replyMarkup = $this->getApi()->replyKeyboardMarkup([
                    'inline_keyboard' => [[['text' => "\xF0\x9F\x91\x8D " . $date, 'callback_data' => 'like']]]
                ]);
                $callbackText = 'Вам это понравилось';        
                break;
            default:
                throw new \Exception('Unsupported inline keyboard query via Telegram API! (' . $query . ')');
                break;
        }

        try {
            $this->getApi()->editMessageReplyMarkup([
                'chat_id' => $chatId,
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