<?php

namespace DevGroup\DataStructure\tests\models;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\TagDependencyHelper\TagDependencyTrait;

class Product extends \yii\db\ActiveRecord
{
    use PropertiesTrait;
    use TagDependencyTrait;

    public static function tableName()
    {
        return '{{%product}}';
    }

    public function behaviors()
    {
        return [
            'CacheableActiveRecord' => [
                'class' => \DevGroup\TagDependencyHelper\CacheableActiveRecord::className(),
            ],
            [
                'class' => PackedJsonAttributes::className(),
            ],
            [
                'class' => HasProperties::className(),
            ],
        ];
    }
}