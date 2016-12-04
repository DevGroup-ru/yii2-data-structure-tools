<?php

namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\helpers\PropertyStorageHelper;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\DataStructure\propertyStorage\AbstractPropertyStorage;
use DevGroup\Entity\traits\EntityTrait;
use DevGroup\Entity\traits\SoftDeleteTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\ServerErrorHttpException;

/**
 * Class Property
 *
 * @mixin \DevGroup\Multilingual\behaviors\MultilingualActiveRecord
 * @property integer $id
 * @property string $key
 * @property integer $data_type
 * @property boolean $is_internal
 * @property boolean $allow_multiple_values
 * @property integer $storage_id
 * @property integer $property_handler_id
 * @property integer $in_search
 * @property array $default_value
 * @property array $handler_config
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property integer $is_deleted
 * @property PropertyGroup[] $propertyGroups
 * @property PropertyGroup $defaultPropertyGroup
 * @property StaticValue[] $staticValues
 * @property PropertyStorage $storage
 *
 * @package DevGroup\DataStructure\models
 */
class Property extends ActiveRecord
{
    public static $propertyIdToKey = [];

    use MultilingualTrait;
    use TagDependencyTrait;
    use EntityTrait;
    use SoftDeleteTrait;

    // Data types
    const DATA_TYPE_STRING = 0;
    const DATA_TYPE_INTEGER = 1;
    const DATA_TYPE_FLOAT = 2;
    const DATA_TYPE_TEXT = 3;
    const DATA_TYPE_PACKED_JSON = 4;
    const DATA_TYPE_BOOLEAN = 5;
    const DATA_TYPE_INVARIANT_STRING = 6;

    // Handler type
    const TYPE_CUSTOM_VALUE = 1;
    const TYPE_VALUES_LIST = 2;

    // Multiple mode
    const MODE_ALLOW_NOTHING = 10;
    const MODE_ALLOW_SINGLE = 11;
    const MODE_ALLOW_MULTIPLE = 12;
    const MODE_ALLOW_ALL = 13;


    // Packed sub arrays
    const PACKED_HANDLER_PARAMS = 'handlerParams';
    const PACKED_ADDITIONAL_RULES = 'additionalRules';

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::className(),
                'translationPublishedAttribute' => false,
            ],
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::className(),
            ],
            'PackedJsonAttributes' => [
                'class' => PackedJsonAttributes::className(),
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property}}';
    }

    /**
     * @inheritdoc
     */
    protected function getRules()
    {
        return [
            ['key', 'unique'],
            ['key', 'required'],
            ['key', 'string', 'max' => 80],
            [['is_internal', 'allow_multiple_values'], 'filter', 'filter' => 'boolval'],
            [['data_type', 'in_search'], 'integer',],
            [['data_type'], 'required',],
            ['storage_id', 'validateStorage'],
            ['property_handler_id', 'validatePropertyHandler'],
            [['storage_id', 'data_type', 'property_handler_id'], 'filter', 'filter' => 'intval'],
            ['id', 'integer', 'on' => 'search'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['search'] = [
            'id',
            'key',
            'is_internal',
            'allow_multiple_values',
            'data_type',
            'storage_id',
            'property_handler_id',
            'name',
            'in_search',
            'is_deleted'
        ];
        return $scenarios;
    }

    /**
     * @return array
     */
    protected function getAttributeLabels()
    {
        return [
            'id' => Module::t('app', 'ID'),
            'key' => Module::t('app', 'Key'),
            'data_type' => Module::t('app', 'Data Type'),
            'is_internal' => Module::t('app', 'Is Internal'),
            'allow_multiple_values' => Module::t('app', 'Allow Multiple Values'),
            'storage_id' => Module::t('app', 'Storage ID'),
            'packed_json_params' => Module::t('app', 'Packed Json Params'),
            'property_handler_id' => Module::t('app', 'Property Handler ID'),
            'name' => Module::t('app', 'Name'),
            'in_search' => Module::t('app', 'Use in search'),
        ];
    }

    /**
     * @param $propertyGroupId
     * @param $params
     * @param $showHidden
     *
     * @return \yii\data\ActiveDataProvider
     *
     * @codeCoverageIgnore
     */
    public function search($propertyGroupId, $params, $showHidden = false)
    {
        $query = self::find();
        if ($propertyGroupId !== null) {
            $query
                ->innerJoin(
                    '{{%property_property_group}}',
                    'property_property_group.property_id = id'
                )
                ->where(['property_property_group.property_group_id' => $propertyGroupId]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 10,
            ],
        ]);
        $dataProvider->sort->attributes['name'] = [
            'asc' => ['property_group_translation.name' => SORT_ASC],
            'desc' => ['property_group_translation.name' => SORT_DESC],
        ];

        if (!($this->load($params))) {
            if ($showHidden === false) {
                $this->is_deleted = 0;
                $query->andWhere(['is_deleted' => $this->is_deleted]);
            }
            return $dataProvider;
        }


        // perform filtering
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['like', 'key', $this->key]);
        $query->andFilterWhere(['is_internal' => $this->is_internal]);
        $query->andFilterWhere(['data_type' => $this->data_type]);
        $query->andFilterWhere(['is_deleted' => $this->is_deleted]);
        $query->andFilterWhere(['storage_id' => $this->storage_id]);
        $query->andFilterWhere(['property_handler_id' => $this->property_handler_id]);

        // filter by multilingual field
        $query->andFilterWhere(['like', 'property_translation.name', $this->name]);


        return $dataProvider;
    }

    /**
     * Perform beforeSave events and do other needed stuff:
     * - send event to property handler(he may want to force is_numeric for example)
     *
     * @param bool $insert
     *
     * @return bool
     * @throws \Exception
     */
    public function beforeSave($insert)
    {
        $result = parent::beforeSave($insert);
        if ($result === false) {
            return false;
        }

        $storage = PropertyStorageHelper::storageById($this->storage_id);

        $status = $insert ? $storage->beforePropertyAdd($this) : $storage->beforePropertyChange($this);
        if ($status === false) {
            return false;
        }

        $handler = PropertyHandlerHelper::getInstance()->handlerById($this->property_handler_id);
        return $handler->beforePropertyModelSave($this, $insert);
    }

    /**
     * Perform afterSave events:
     * - flush needed caches
     * - trigger afterPropertyModelSave event of corresponding PropertyHandler
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);
        if (isset($changedAttributes['key'])) {
            Yii::$app->cache->delete("PropertyKeyForId:{$this->id}");
        }
        $handler = PropertyHandlerHelper::getInstance()->handlerById($this->property_handler_id);
        $handler->afterPropertyModelSave($this);

        $storage = PropertyStorageHelper::storageById($this->storage_id);

        if ($insert) {
            $storage->afterPropertyAdd($this);
        } else {
            $storage->afterPropertyChange($this, $changedAttributes);
        }
        if (in_array('storage_id', $changedAttributes) === true) {
            $apmIds = ArrayHelper::getColumn($this->propertyGroups, 'applicable_property_model_id');
            foreach (ApplicablePropertyModels::find()->select('class_name')->where(['id' => $apmIds])->column() as $className) {
                PropertyStorageHelper::clearHandlersForClass($className);
            }
        }
    }

    /**
     * Perform beforeDelete events
     *
     * @return bool events status
     * @throws ServerErrorHttpException
     */
    public function beforeDelete()
    {
        if (parent::beforeDelete() === false) {
            return false;
        }
        $storage = PropertyStorageHelper::storageById($this->storage_id);
        return $storage->beforePropertyDelete($this);
    }

    /**
     * Perform afterDelete events
     * @throws ServerErrorHttpException
     */
    public function afterDelete()
    {
        $storage = PropertyStorageHelper::storageById($this->storage_id);
        $storage->afterPropertyDelete($this);
    }

    /**
     * Perform beforeValidate events
     * @return bool
     * @throws ServerErrorHttpException
     */
    public function beforeValidate()
    {
        $validation = parent::beforeValidate();
        return $validation && PropertyStorageHelper::storageById($this->storage_id)->beforePropertyValidate($this);
    }

    /**
     * Perform afterValidate events
     * @throws ServerErrorHttpException
     */
    public function afterValidate()
    {
        parent::afterValidate();
        PropertyStorageHelper::storageById($this->storage_id)->afterPropertyValidate($this);
    }

    /**
     * Perform afterFind events, fill static variable(per-process memory cache)
     */
    public function afterFind()
    {
        parent::afterFind();
        static::$propertyIdToKey[$this->id] = $this->key;
        static::$identityMap[$this->id] = $this;

        // cast scalar values
        $this->allow_multiple_values = (bool) $this->allow_multiple_values;
        $this->is_internal = (bool) $this->is_internal;
        $this->data_type = (int) $this->data_type;
        $this->property_handler_id = (int) $this->property_handler_id;
    }

    /**
     * Checks should this property values to be translated or not
     *
     * @return bool
     */
    public function canTranslate()
    {
        return in_array($this->data_type, [self::DATA_TYPE_STRING, self::DATA_TYPE_TEXT]);
    }

    /**
     * Returns property key by property id.
     * Uses identityMap, $propertyIdToKey static variable(per-process memory cache), lazy cache for db query
     *
     * @param int $propertyId
     *
     * @return string|null
     */
    public static function propertyKeyForId($propertyId)
    {
        if (isset(static::$identityMap[$propertyId])) {
            return static::$identityMap[$propertyId]->key;
        }
        if (isset(static::$propertyIdToKey[$propertyId])) {
            return static::$propertyIdToKey[$propertyId];
        }

        static::$propertyIdToKey[$propertyId] = Yii::$app->cache->lazy(
            function () use ($propertyId) {
                $query = new Query();
                return $query
                    ->select(['key'])
                    ->from(static::tableName())
                    ->where(['id' => $propertyId])
                    ->scalar(static::getDb());
            },
            "PropertyKeyForId:$propertyId",
            86400
        );
        return static::$propertyIdToKey[$propertyId];
    }

    /**
     * Returns PropertyHandler for current property
     *
     * @return \DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler
     * @throws \Exception
     */
    public function handler()
    {
        return PropertyHandlerHelper::getInstance()->handlerById($this->property_handler_id);
    }

    /**
     * Casts value to data type
     *
     * @param mixed $value
     * @param integer $type
     *
     * @return mixed
     */
    public static function castValueToDataType($value, $type)
    {
        switch ($type) {
            case Property::DATA_TYPE_FLOAT:
                return empty($value) ? null : (float) $value;
                break;

            case Property::DATA_TYPE_BOOLEAN:
                return empty($value) ? null : (bool) $value;
                break;

            case Property::DATA_TYPE_INTEGER:
                return empty($value) ? null : (int) $value;
                break;

            default:
                return $value;
                break;
        }
    }

    /**
     * Returns name of function for filtering, data casting
     * @param $type
     * @return null|string
     */
    public static function validationCastFunction($type)
    {
        switch ($type) {
            case Property::DATA_TYPE_FLOAT:
                return 'floatval';
                break;

            case Property::DATA_TYPE_BOOLEAN:
                return 'boolval';
                break;

            case Property::DATA_TYPE_INTEGER:
                return 'intval';
                break;

            case Property::DATA_TYPE_STRING:
            case Property::DATA_TYPE_TEXT:
            case self::DATA_TYPE_INVARIANT_STRING:
                return 'strval';
                break;

            default:
                return null;
        }
    }

    /**
     * Returns name of validator used for validation based on data type
     * @param $type
     * @return null|string
     */
    public static function dataTypeValidator($type)
    {
        switch ($type) {
            case Property::DATA_TYPE_FLOAT:
                return 'double';
                break;

            case Property::DATA_TYPE_BOOLEAN:
                return 'bool';
                break;

            case Property::DATA_TYPE_INTEGER:
                return 'integer';
                break;

            case Property::DATA_TYPE_STRING:
            case Property::DATA_TYPE_TEXT:
            case self::DATA_TYPE_INVARIANT_STRING:
                return 'string';
                break;
            default:
                return null;
        }
    }

    /**
     * A proxy method for LoadModel
     * @param integer $id
     * @param boolean $throwException
     * @return Property
     * @throws \Exception
     */
    public static function findById($id, $throwException = true)
    {
        $e = $throwException ? new ServerErrorHttpException("Property with id $id not found") : false;

        return static::loadModel(
            $id,
            false,
            true,
            86400,
            $e,
            true
        );
    }

    /**
     * Returns properties by ids
     * @param int[] $ids
     * @param bool  $throwException
     * @todo  Add SMART cache here
     *
     * @return mixed
     */
    public static function findByIds($ids, $throwException = true)
    {
        $ids = array_unique($ids);
        $ids = array_map('intval', $ids);
        $ids2find = array_diff($ids, array_keys(static::$identityMap));
        if (count($ids2find) > 0) {
            Property::find()->where(['in', 'id', $ids2find])->all();
        }

        return array_reduce(
            $ids,
            function ($carry, $id) use ($throwException) {
                if (isset(static::$identityMap[$id])) {
                    $carry[$id] = static::$identityMap[$id];
                } elseif ($throwException) {
                    throw new \RuntimeException("Property $id not found");
                }
                return $carry;
            },
            []
        );
    }

    /**
     * @return array of all data types
     */
    public static function getDataTypes()
    {
        return [
            self::DATA_TYPE_STRING,
            self::DATA_TYPE_INTEGER,
            self::DATA_TYPE_FLOAT,
            self::DATA_TYPE_TEXT,
            self::DATA_TYPE_PACKED_JSON,
            self::DATA_TYPE_BOOLEAN,
            self::DATA_TYPE_INVARIANT_STRING,
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPropertyGroups()
    {
        return $this->hasMany(PropertyGroup::className(), ['id' => 'property_group_id'])
            ->viaTable(
                '{{%property_property_group}}',
                [
                    'property_id' => 'id'
                ]
            )->orderBy(['{{%property_group}}.sort_order' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getDefaultPropertyGroup()
    {
        return $this->hasOne(PropertyGroup::className(), ['id' => 'property_group_id'])
            ->viaTable(
                '{{%property_property_group}}',
                [
                    'property_id' => 'id'
                ]
            )->orderBy([PropertyGroup::tableName() . '.sort_order' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStaticValues()
    {
        return $this->hasMany(StaticValue::className(), ['property_id' => 'id'])
            ->orderBy([StaticValue::tableName() . '.sort_order' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getStorage()
    {
        // please don't use relation if not needed, use storage() otherwise
        return $this->hasOne(PropertyStorage::className(), ['id' => 'storage_id']);
    }

    /**
     * @return PropertyStorage
     */
    public function storage()
    {
        return PropertyStorage::loadModel($this->storage_id, false, true, 86400, true, true);
    }

    public function afterBind($propertyGroup)
    {
        /** @var AbstractPropertyStorage $storageClassName */
        $storageClassName = $this->storage->class_name;
        $storageClassName::afterBind($this, $propertyGroup);
    }

    public function afterUnbind($propertyGroup)
    {
        /** @var AbstractPropertyStorage $storageClassName */
        $storageClassName = $this->storage->class_name;
        $storageClassName::afterUnbind($this, $propertyGroup);
    }

    /**
     * check if property required
     * @return bool
     */
    public function isRequired()
    {
        $params = $this->params;
        $required = boolval(ArrayHelper::getValue($params, Property::PACKED_ADDITIONAL_RULES . '.required', false));
        return $required;
    }

    /**
     * @param $attribute
     * @param $params
     */
    public function validateStorage($attribute, $params)
    {
        try {
            PropertyStorageHelper::storageById($this->$attribute);
        } catch (ServerErrorHttpException $e) {
            $this->addError($attribute, 'Storage not found');
        }
    }

    public function validatePropertyHandler($attribute, $params)
    {
        try {
            PropertyHandlerHelper::getInstance()->handlerById($this->$attribute);
        } catch (\Exception $e) {
            $this->addError($attribute, 'Property Handler not found');
        }
    }
}
