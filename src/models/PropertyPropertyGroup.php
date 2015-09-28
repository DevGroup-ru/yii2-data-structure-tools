<?php
namespace DevGroup\DataStructure\models;

use Yii;
use yii\db\ActiveRecord;

class PropertyPropertyGroup extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_property_group}}';
    }
}