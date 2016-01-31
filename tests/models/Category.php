<?php

namespace DevGroup\DataStructure\tests\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\traits\PropertiesTrait;

class Category extends \yii\db\ActiveRecord
{
    use PropertiesTrait;

    public static function tableName()
    {
        return '{{%category}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => HasProperties::className(),
            ],
            'ContinuousNumericalSortableBehavior' => [
                'class' => ContinuousNumericalSortableBehavior::className(),
                'sortAttribute' => 'sort_order'
            ],
        ];
    }
}