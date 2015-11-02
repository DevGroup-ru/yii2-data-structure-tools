<?php
namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\helpers\PropertyHandlerHelper;
use DevGroup\DataStructure\helpers\PropertyStorageHelper;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\web\ServerErrorHttpException;

/**
 * Class Property
 *
 * @mixin \DevGroup\Multilingual\behaviors\MultilingualActiveRecord
 * @property integer $id
 * @property string  $key
 * @property integer $data_type
 * @property boolean $is_internal
 * @property boolean $allow_multiple_values
 * @property integer $storage_id
 * @property integer $property_handler_id
 * @property array   $default_value
 * @property array   $handler_config
 * @property integer $applicable_property_model_id
 *
 * @package DevGroup\DataStructure\models
 */
class Property extends ActiveRecord
{
    public static $propertyIdToKey = [];

    use MultilingualTrait;
    use TagDependencyTrait;

    const DATA_TYPE_STRING         = 0;
    const DATA_TYPE_INTEGER        = 1;
    const DATA_TYPE_FLOAT          = 2;
    const DATA_TYPE_TEXT           = 3;
    const DATA_TYPE_PACKED_JSON    = 4;
    const DATA_TYPE_BOOLEAN        = 5;

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
    public function rules()
    {
        return [
            ['applicable_property_model_id', 'integer',],
            ['applicable_property_model_id', 'required',],
            ['key', 'unique'],
            ['key', 'required'],
            ['key', 'string', 'max'=>80],
            [['is_internal', 'allow_multiple_values'], 'filter', 'filter'=>'boolval'],
            [['data_type'], 'integer',],
            [['data_type'], 'required',],
            ['storage_id', function ($attribute) {
                return PropertyStorageHelper::storageById($this->$attribute);
            }],
            [['storage_id', 'data_type'], 'filter', 'filter'=>'intval'],
        ];
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
     * @param bool  $insert
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
            $storage->afterPropertyChange($this);
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

        // cast scalar values
        $this->allow_multiple_values = boolval($this->allow_multiple_values);
        $this->is_internal = boolval($this->is_internal);
        $this->data_type = intval($this->data_type);
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
     */
    public static function castValueToDataType($value, $type)
    {
        switch ($type) {
            case Property::DATA_TYPE_FLOAT:
                return floatval($value);
                break;

            case Property::DATA_TYPE_BOOLEAN:
                return boolval($value);
                break;

            case Property::DATA_TYPE_INTEGER:
                return intval($value);
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
                return 'string';
                break;
            default:
                return null;
        }
    }

    /**
     * A proxy method for LoadModel
     * @param $id
     * @return Property
     * @throws \Exception
     */
    public static function findById($id)
    {
        return static::loadModel(
            $id,
            false,
            true,
            86400,
            new ServerErrorHttpException("Property with id $id not found"),
            true
        );
    }
}
