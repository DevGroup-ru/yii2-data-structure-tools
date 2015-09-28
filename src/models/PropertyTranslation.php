<?php
namespace DevGroup\DataStructure\models;

use Yii;
use yii\db\ActiveRecord;

class PropertyTranslation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_translation}}';
    }
}