<?php

namespace DevGroup\DataStructure\tests\models;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use yii\helpers\ArrayHelper;

/**
 * Class Product
 * @package DevGroup\DataStructure\tests\models
 * @mixin HasProperties
 * @mixin \DevGroup\TagDependencyHelper\CacheableActiveRecord
 */
class Product extends \yii\db\ActiveRecord
{
    use PropertiesTrait;
    use TagDependencyTrait;

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [['name'], 'string',],
                ['packed_json_data', 'safe'],
                ['sort_order', 'integer'],
            ],
            $this->propertiesRules()
        );
    }
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%product}}';
    }

    public function behaviors()
    {
        return [
            [
                'class' => PackedJsonAttributes::className(),
            ],
            [
                'class' => HasProperties::className(),
            ],
            'CacheableActiveRecord' => [
                'class' => \DevGroup\TagDependencyHelper\CacheableActiveRecord::className(),
            ],
        ];
    }
}