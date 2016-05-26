<?php

namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\Properties\Module;
use Yii;

/**
 * This is the model class for table "{{%applicable_property_models}}".
 *
 * @property integer $id
 * @property string $class_name
 * @property string $name
 */
class ApplicablePropertyModels extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%applicable_property_models}}';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['class_name', 'name'], 'required'],
            [['class_name', 'name'], 'string', 'max' => 255],
            [['class_name'], 'unique', 'targetAttribute' => 'class_name'],

        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => Module::t('app', 'ID'),
            'class_name' => Module::t('app', 'Class Name'),
            'name' => Module::t('app', 'Name'),
        ];
    }

    /**
     * Returns self id by given class name or false if not found
     *
     * @param string $className
     * @return bool|string
     */
    public static function getIdForClass($className = '')
    {
        return self::find()->select('id')->where(['class_name' => $className])->scalar();
    }
}
