<?php

namespace frontend\models;

use Yii;
use yii\base\Model;

/**
 * This is the model class for table "telegram_table".
 *
 * @property int $id
 * @property int $chat_id
 * @property int $user_id
 * @property string $options
 * @property boolean $is_admin
 */
class TelegramBot extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return 'telegram_table';
    }

    public function rules()
    {
        return [
            [['id', 'chat_id', 'user_id'], 'integer'],
            [['options'], 'string', 'max' => 65535],
            [['is_admin'], 'boolean'],
            [['id', 'chat_id', 'user_id', 'options', 'is_admin'], 'safe'],
            [['chat_id'], 'required'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_id' => Module::t('app', 'Chatid'),
            'user_id' => Module::t('app', 'Userid'),
            'options' =>  Module::t('app', 'Options'),
            'is_admin' => Module::t('app', 'Isadmin'),
        ];
    }
}