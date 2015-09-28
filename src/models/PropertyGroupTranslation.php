<?php
namespace DevGroup\DataStructure\models;

use Yii;
use yii\db\ActiveRecord;

class PropertyGroupTranslation extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_group_translation}}';
    }
}