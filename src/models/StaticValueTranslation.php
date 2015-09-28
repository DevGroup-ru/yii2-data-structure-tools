<?php
namespace DevGroup\DataStructure\models;

use Yii;
use yii\db\ActiveRecord;

class StaticValueTranslation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_value_translation}}';
    }
}