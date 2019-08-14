<?php

namespace frontend\components;

use aki\telegram\Telegram;
use frontend\models\TelegramBot;
use common\models\Order;
use common\models\Question;
use yii\base\Component;
use Yii;

class SendTelegram extends Telegram
{
    public $send = true;
    public $host;

    public function init()
    {
        parent::init();
        $protocol = ($_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://';
        $this->host = $protocol.$_SERVER['SERVER_NAME'];
    }

    public function sendOrder(Order $order)
    {
        if(!$this->send) {
            return true;
        }
        $txt  = '<b>HealthyMeal Новый заказ</b>'.PHP_EOL.PHP_EOL;
        $txt .= '<b>ID: </b>'.$order->id.PHP_EOL;
        $txt .= '<b>Имя клиента: </b>'.$order->username.PHP_EOL;
        $txt .= '<b>Телефон: </b>'.$order->phone.PHP_EOL;
        $txt .= '<b>Тип: </b>'.$order->type.PHP_EOL;
        $txt .= '<b>Название: </b>'.$order->name.PHP_EOL;
        $txt .= '<b>Город: </b>'.$order->city.PHP_EOL;
        $txt .= '<b>День: </b>'.$order->day.PHP_EOL;
        $txt .= '<b>Цена: </b>'.$order->price.PHP_EOL;
        $txt .= '<b>Ссылка: </b>'.$this->host.'/admin/order/default/update?id='.$order->id.PHP_EOL;

        return $this->sendMesage2BotChats($txt);
    }

    public function sendQuestion(Question $question)
    {
        if(!$this->send) {
            return true;
        }
        $txt  = '<b>HealthyMeal Новый вопрос</b>'.PHP_EOL.PHP_EOL;
        $txt .= '<b>Телефон: </b>'.$question->phone.PHP_EOL;
        $txt .= '<b>Вопрос: </b>'.$question->text.PHP_EOL;
        $txt .= '<b>Ссылка: </b>'.$this->host.'/admin/question/default/update?id='.$question->id.PHP_EOL;

        return $this->sendMesage2BotChats($txt);
    }

    public function sendMesage2BotChats($str)
    {
        $query = TelegramBot::find()->all();
        if ($query) {
            foreach ($query as $item) {
                if ($item->is_admin) {
                    $this->sendMessage([
                        'chat_id' => $item->chat_id,
                        'text' => $str,
                        'parse_mode' => 'HTML'
                    ]);
                }
            }
            return true;
        } else {
            return false;
        }
    }
}