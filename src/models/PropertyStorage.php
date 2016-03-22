<?php

namespace DevGroup\DataStructure\models;

use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;

/**
 * This is the model class for table "{{%property_storage}}".
 *
 * @property integer $id
 * @property string $name
 * @property string $class_name
 * @property integer $sort_order
 */
class PropertyStorage extends \yii\db\ActiveRecord
{
    use TagDependencyTrait;

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_storage}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'class_name'], 'required'],
            [['sort_order'], 'integer'],
            [['name', 'class_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'name' => Yii::t('app', 'Name'),
            'class_name' => Yii::t('app', 'Class Name'),
            'sort_order' => Yii::t('app', 'Sort Order'),
        ];
    }
}
