<?php

namespace app\models;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\traits\PropertiesTrait;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "{{%product}}".
 *
 * @property integer $id
 * @property string $price
 * @property integer $sort_order
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
                [['price'], 'number'],
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

    /**
     * @inheritdoc
     */
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

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'price' => Yii::t('app', 'Price'),
            'sort_order' => Yii::t('app', 'Sort Order'),
        ];
    }
}
