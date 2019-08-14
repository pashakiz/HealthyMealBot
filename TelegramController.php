<?php

namespace frontend\controllers;

use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use frontend\models\TelegramBot;
use common\models\User;
use frontend\components\SendTelegram;

class TelegramController extends Controller
{
    public $login_text_step_input_login = 'Введите логин:';
    public $login_text_step_input_login_error = 'Ведите другой логин:';
    public $login_text_step_input_pass = 'Введите пароль';
    public $login_text_step_input_pass_error = 'Не верный пароль. Попробуйте еще раз:';

    public function beforeAction($action)
    {
        $this->enableCsrfValidation = ($action->id !== "index");
        return parent::beforeAction($action);
    }

	public function actionIndex()
    {
        $data = file_get_contents('php://input');
        //file_put_contents('tbotlog-answer.txt', $data);
        $data = json_decode($data, true);

        $message_text = isset($data['message']['text']) ? $data['message']['text'] : false;
        $chat_id = isset($data['message']['chat']['id']) ? $data['message']['chat']['id'] : false;
        $previous_message = $data['message']['reply_to_message']['text'];
        $callback_data = isset($data['callback_query']['data']) ? $data['callback_query']['data'] : false;
        if (!$message_text && $callback_data) {
            $message_text = isset($data['callback_query']['message']['text']) ? $data['callback_query']['message']['text'] : false;
            $chat_id = isset($data['callback_query']['message']['chat']['id']) ? $data['callback_query']['message']['chat']['id'] : false;
        }

        if ($chat_id) {

            if ($previous_message) {

                //'Введите логин:' ИЛИ 'Ведите другой логин:'
                if ( (strpos($previous_message, $this->login_text_step_input_login) !== false) || (strpos($previous_message, $this->login_text_step_input_login_error) !== false) ) {

                    $login = $message_text;
                    if (User::findByUsername($login)) {
                        $txt = $this->login_text_step_input_pass.' для &quot;<b>'.$login.'</b>&quot;:';
                    } else {
                        $txt = 'Пользователя с логином &quot;<b>'.$login.'</b>&quot; не существует. '.$this->login_text_step_input_login_error;
                    }

                    $reply_markup = json_encode(
                        array(
                            'force_reply' => true,
                            'selective' => true
                        )
                    );

                    Yii::$app->sendTelegram->sendMessage([
                        'chat_id'=>$chat_id,
                        'text'=>$txt,
                        'parse_mode' => 'HTML',
                        'reply_markup' => $reply_markup
                    ]);

                    exit;
                }

                //'Введите пароль' ИЛИ 'Не верный пароль. Попробуйте еще раз:'
                if ( (strpos($previous_message, $this->login_text_step_input_pass) !== false) || (strpos($previous_message, $this->login_text_step_input_pass_error) !== false) ) {

                    $password = $message_text;
                    $str = explode('"', $previous_message);
                    $login = $str[1];
                    $user = User::findByUsername($login);
                    if ($user->validatePassword($password)) {

                        $q = TelegramBot::find()
                            ->where(['chat_id' => $chat_id])
                            ->one();

                        if ($q) { //user found
                            $q->is_admin = 1;
                            //$q->user_id = $user->id;
                            $q->save();
                        }

                        $txt  = 'Вы успешно авторизованы!'.PHP_EOL;
                        $txt .= 'Как только на сайте '.$_SERVER['SERVER_NAME'].' появиться новый заказ или вопрос - вы получите уведоление этом чате.';
                        Yii::$app->sendTelegram->sendMessage([
                            'chat_id'=>$chat_id,
                            'text'=>$txt
                        ]);
                    } else {
                        $txt  = 'Не верный пароль.'.PHP_EOL;;
                        $txt .= $this->login_text_step_input_pass.' для &quot;<b>'.$login.'</b>&quot;:';
                        $reply_markup = json_encode(
                            array(
                                'force_reply' => true,
                                'selective' => true
                            )
                        );
                        Yii::$app->sendTelegram->sendMessage([
                            'chat_id'=>$chat_id,
                            'text'=>$txt,
                            'parse_mode' => 'HTML',
                            'reply_markup' => $reply_markup
                        ]);
                    }

                    exit;
                }
            }

            if ( (strpos($message_text, '/login') !== false) || (strpos($callback_data, '/login') !== false) ) {

                $txt = $this->login_text_step_input_login;
                $reply_markup = json_encode(
                    array(
                        'force_reply' => true,
                        'selective' => true
                    )
                );
                Yii::$app->sendTelegram->sendMessage([
                    'chat_id'=>$chat_id,
                    'text'=>$txt,
                    'reply_markup' => $reply_markup
                ]);

                exit;
            }

            if (strpos($message_text, '/start') !== false) {

                $keyboard_login_text = 'Авторизация';
                $txt_push_btn .= 'Нажмите на кнопку *'.$keyboard_login_text.'* ниже, чтобы войти, как администратор сайта и начать получать уведомления.';
                $reply_markup = json_encode([
                    'inline_keyboard'=>[
                        [
                            ['text' => $keyboard_login_text, 'callback_data' => '/login']
                        ]
                    ]
                ]);

                $query = TelegramBot::find()
                    ->where(['chat_id' => $chat_id])
                    ->one();

                if ($query) { //user found
                    $txt  = 'Мы уже знакомы...'.PHP_EOL;
                    $txt .= $txt_push_btn;
                } else { //user not found
                    $t_user = new TelegramBot();
                    $t_user->chat_id = $chat_id;
                    if ( $t_user->save() ) {
                        $txt  = 'Добро пожаловать! Это бот сайта: '.$_SERVER['SERVER_NAME'].PHP_EOL;
                        $txt .= $txt_push_btn;
                    } else {
                        $txt = 'Error: 500. Что-то пошло не так :(';
                    }
                }
                Yii::$app->sendTelegram->sendMessage([
                    'chat_id'=>$chat_id,
                    'text'=>$txt,
                    'parse_mode' => 'Markdown',
                    'reply_markup' => $reply_markup,
                ]);

            } else {
                $txt = 'По запросу *'.$message_text.'* ничего не найдено.'.PHP_EOL;
                Yii::$app->sendTelegram->sendMessage([
                    'chat_id'=>$chat_id,
                    'text'=>$txt,
                    'parse_mode' => 'Markdown',
                ]);
            }
        } else {
            echo 'Ты кто такой и что здесь делаешь?';
        }
	}
}

?>