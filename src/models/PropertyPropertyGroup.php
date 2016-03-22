<?php

namespace DevGroup\DataStructure\models;

use Yii;

/**
 * This is the model class for table "{{%property_property_group}}".
 *
 * @property integer $property_id
 * @property integer $property_group_id
 * @property integer $sort_order_property_groups
 * @property integer $sort_order_group_properties
 *
 * @property PropertyGroup $propertyGroup
 * @property Property $property
 */
class PropertyPropertyGroup extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_property_group}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['property_id', 'property_group_id'], 'required'],
            [['property_id', 'property_group_id', 'sort_order_property_groups', 'sort_order_group_properties'], 'integer'],
            [['property_group_id'], 'exist', 'skipOnError' => true, 'targetClass' => PropertyGroup::className(), 'targetAttribute' => ['property_group_id' => 'id']],
            [['property_id'], 'exist', 'skipOnError' => true, 'targetClass' => Property::className(), 'targetAttribute' => ['property_id' => 'id']],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'property_id' => Yii::t('app', 'Property'),
            'property_group_id' => Yii::t('app', 'Property Group'),
            'sort_order_property_groups' => Yii::t('app', 'Sort Order Property Groups'),
            'sort_order_group_properties' => Yii::t('app', 'Sort Order Group Properties'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPropertyGroup()
    {
        return $this->hasOne(PropertyGroup::className(), ['id' => 'property_group_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProperty()
    {
        return $this->hasOne(Property::className(), ['id' => 'property_id']);
    }

    public function afterSave($insert, $changedAttributes)
    {
        if ($insert === true) {
            $this->property->afterBind($this->propertyGroup);
        }
        parent::afterSave($insert, $changedAttributes);
    }

    public function afterDelete()
    {
        $this->property->afterUnbind($this->propertyGroup);
        parent::afterDelete();
    }
}
