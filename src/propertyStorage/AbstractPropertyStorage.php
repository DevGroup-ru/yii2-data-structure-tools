<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\behaviors\HasProperties;
use DevGroup\DataStructure\models\ApplicablePropertyModels;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\models\PropertyPropertyGroup;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\DataStructure\search\interfaces\Filter;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Yii;
use yii\base\Exception;
use yii\caching\ChainedDependency;
use yii\caching\Dependency;
use yii\caching\TagDependency;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

/**
 * Class AbstractPropertyStorage
 *
 * @todo list:
 * - abstract function that for moving from one storage type to another
 *
 * @package DevGroup\DataStructure\propertyStorage
 */
abstract class AbstractPropertyStorage implements Filter
{
    /**
     * @var ActiveRecord[] | HasProperties[] | PropertiesTrait[] Applicable property model class names identity map by property id
     */
    protected static $applicablePropertyModelClassNames = [];

    /**
     * Get applicable property model class names by property id.
     * @param int $id
     * @return ActiveRecord[] | HasProperties[] | PropertiesTrait[]
     */
    protected static function getApplicablePropertyModelClassNames($id)
    {
        if (isset(static::$applicablePropertyModelClassNames[$id]) === false) {
            $subQuery = PropertyPropertyGroup::find()
                ->from(PropertyPropertyGroup::tableName() . ' ppg')
                ->select('pg.applicable_property_model_id')
                ->join('INNER JOIN', PropertyGroup::tableName() . ' pg', 'pg.id = ppg.property_group_id')
                ->where(['ppg.property_id' => $id])
                ->createCommand()->getRawSql();
            static::$applicablePropertyModelClassNames[$id] = (new Query())
                ->select('class_name')
                ->from(ApplicablePropertyModels::tableName())
                ->where('id IN (' . $subQuery . ')')->column();
        }
        return static::$applicablePropertyModelClassNames[$id];
    }

    /**
     * @var int ID of storage in property_storage table
     */
    public $storageId = null;

    /**
     * @param int $storageId ID of storage in property_storage table
     */
    public function __construct($storageId)
    {
        $this->storageId = intval($storageId);
    }

    /**
     * Helper method
     *
     * @param $returnType
     * @param ActiveQuery $tmpQuery
     * @param $result
     * @param $className
     * @param Dependency $dependency
     * @param int $cacheLifetime
     *
     * @return array|int
     */
    protected static function valueByReturnType(
        $returnType,
        $tmpQuery,
        $result,
        $className,
        $dependency,
        $cacheLifetime = 86400
    )
    {
        switch ($returnType) {
            case Filter::RETURN_COUNT:
                $result += $className::getDb()->cache(
                    function ($db) use ($tmpQuery) {
                        return $tmpQuery->count('*', $db);
                    },
                    $cacheLifetime,
                    $dependency
                );

                break;
            case Filter::RETURN_QUERY:
                $result[$className] = $tmpQuery;
                break;
            default:
                if (!empty($tmpQuery)) {
                    $result = ArrayHelper::merge(
                        $result,
                        $className::getDb()->cache(
                            function ($db) use ($tmpQuery) {
                                return $tmpQuery->all($db);
                            },
                            $cacheLifetime,
                            $dependency
                        )
                    );
                }
        }
        return $result;
    }

    /**
     * @param $params
     * @param $column
     *
     * @return mixed
     * @throws Exception
     */
    protected static function prepareParams($params, $column)
    {
        if (is_string($params)) {
            $params = str_replace('[column]', $column, $params);
            return $params;
        } elseif (is_array($params)) {
            $params = Json::decode(str_replace('[column]', $column, Json::encode($params)));
            return $params;
        } else {
            throw new Exception(Module::t('app', 'Params should be string or array'));
        }
    }

    /**
     * @param Query[] $queries
     *
     * @return Query
     * @throws Exception
     */
    protected static function unionQueriesToOne($queries)
    {
        if (count($queries) === 0) {
            throw new Exception(Module::t('app', 'Nothing to union'));
        }

        $query = array_reduce(
            $queries,
            function ($query, $item) {
                /**
                 * @var $query Query
                 */
                if ($query === null) {
                    $query = $item;
                } else {
                    $query->union($item);
                }
                return $query;
            }
        );

        return $query;
    }

    /**
     * @param $customDependency
     * @param $tags
     *
     * @return ChainedDependency|TagDependency
     */
    protected static function dependencyHelper($customDependency, $tags)
    {
        if (is_null($customDependency)) {
            $dependency = new TagDependency(['tags' => $tags]);
            return $dependency;
        } elseif (is_string($customDependency)) {
            $tags[] = $customDependency;
            $dependency = new TagDependency(['tags' => $tags]);
            return $dependency;
        } else {
            $dependency = new ChainedDependency(
                ['dependencies' => [$customDependency, new TagDependency(['tags' => $tags])]]
            );
            return $dependency;
        }
    }

    /**
     * Fills $models array models with corresponding binded properties.
     * Models in $models array should be the the same class name.
     *
     * @param ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[]|\DevGroup\DataStructure\behaviors\HasProperties[] $models
     *
     * @return ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[]|\DevGroup\DataStructure\behaviors\HasProperties[]
     */
    abstract public function fillProperties(&$models);

    /**
     * Removes all properties binded to models.
     * Models in $models array should be the the same class name.
     *
     * @param ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[]|\DevGroup\DataStructure\behaviors\HasProperties[] $models
     *
     * @return void
     */
    abstract public function deleteAllProperties(&$models);

    /**
     * @return string Returns class name
     */
    public static function className()
    {
        return get_called_class();
    }

    /**
     * @param ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[]|\DevGroup\DataStructure\behaviors\HasProperties[] $models
     *
     * @return boolean
     */
    abstract public function storeValues(&$models);

    /**
     * Action that should be done by property storage before property adding.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @return bool Success status, true if all's ok
     */
    public function beforePropertyAdd(Property &$property)
    {
        return true;
    }

    /**
     * Action that should be done by property storage after property adding.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @return void
     */
    public function afterPropertyAdd(Property &$property)
    {

    }

    /**
     * Action that should be done by property storage before property change.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @return bool Success status, true if all's ok
     */
    public function beforePropertyChange(Property &$property)
    {
        return true;
    }

    /**
     * Action that should be done by property storage after property change.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @param array $changedAttributes
     */
    public function afterPropertyChange(Property &$property, $changedAttributes)
    {

    }

    /**
     * Action that should be done by property storage before property deletion.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @return bool Success status, true if all's ok
     */
    public function beforePropertyDelete(Property &$property)
    {
        return true;
    }

    /**
     * Action that should be done by property storage after property deletion.
     * Property storage can override this function to add specific actions like schema manipulation.
     * @param Property $property Reference to property model
     * @return void
     */
    public function afterPropertyDelete(Property &$property)
    {

    }

    /**
     * Action that should be done by property storage before property validation.
     * Property storage can override this function to add specific actions like redefining data type.
     * @param Property $property Reference to property model
     * @return bool Success status, true if all's ok
     */
    public function beforePropertyValidate(Property &$property)
    {
        return true;
    }

    /**
     * Action that should be done by property storage after property validation.
     * Property storage can override this function to add specific actions like redefining data type.
     * @param Property $property Reference to property model
     * @return void
     */
    public function afterPropertyValidate(Property &$property)
    {

    }

    /**
     * Special event after models with possible properties inserted to db.
     * Used for example for creating index document or table inheritance row.
     *
     * @param ActiveRecord[]|\DevGroup\DataStructure\traits\PropertiesTrait[]|\DevGroup\DataStructure\behaviors\HasProperties[] $models
     */
    public function modelsInserted(&$models)
    {

    }

    /**
     * @param ActiveRecord[] | PropertiesTrait[] | HasProperties[] $models
     * @param int[] $propertyIds
     */
    public function deleteProperties($models, $propertyIds)
    {

    }

    public static function afterBind($property, $propertyGroup)
    {

    }

    public static function afterUnbind($property, $propertyGroup)
    {

    }

    /**
     * @inheritdoc
     */
    public static function getPropertyValuesByParams(
        $propertyId,
        $params = '',
        $customDependency = null,
        $customKey = '',
        $cacheLifetime = 86400
    )
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getModelsByPropertyValues(
        $propertyId,
        $values = [],
        $returnType = self::RETURN_ALL,
        $customDependency = null,
        $cacheLifetime = 86400
    )
    {
        switch ($returnType) {
            case self::RETURN_COUNT:
                return 0;
                break;
            default:
                return [];
        }
    }

    /**
     * @inheritdoc
     */
    public static function filterFormSet(
        $modelClass,
        $props,
        $customDependency = null,
        $cacheLifetime = 86400)
    {
        return [];
    }

    /**
     * @inheritdoc
     */
    public static function getModelsByValueIds(
        $modelClass,
        $selections,
        $customDependency = null,
        $cacheLifetime = 86400
    )
    {
        return [];
    }
}
