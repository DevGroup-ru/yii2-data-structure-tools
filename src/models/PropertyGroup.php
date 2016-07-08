<?php
namespace DevGroup\DataStructure\models;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\Entity\traits\EntityTrait;
use DevGroup\Entity\traits\SoftDeleteTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\NamingHelper;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Query;

/**
 * Class PropertyGroup
 *
 * @property integer $id
 * @property integer $property_group_model_id
 * @property integer $sort_order
 * @property boolean $is_auto_added
 * @property integer $is_deleted
 * @property string $internal_name
 * @property Property[] $properties Properties via relation! Don't use it in frontend as it is not cacheable.
 *
 * @mixin \DevGroup\Multilingual\behaviors\MultilingualActiveRecord
 */
class PropertyGroup extends ActiveRecord
{

    private static $groupIdToPropertyIds = [];

    use MultilingualTrait;
    use TagDependencyTrait;
    use EntityTrait;
    use SoftDeleteTrait;


    protected $rules = [
        [['internal_name'], 'required',],
        [['sort_order', 'applicable_property_model_id'], 'integer'],
        [['sort_order'], 'default', 'value' => 0],
        [['id'], 'integer', 'on' => 'search'],
        [['is_auto_added'], 'filter', 'filter' => 'boolval'],
        ['applicable_property_model_id', 'validateApplicablePropertyMode'],
    ];

    /**
     * PropertyGroup constructor.
     *
     * @param string $className
     * @param array $config
     */
    public function __construct($className = null, array $config = [])
    {
        parent::__construct($config);
        if ($className !== null) {
            $this->applicable_property_model_id = PropertiesHelper::applicablePropertyModelId($className);
        }
    }


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
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%property_group}}';
    }


    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        $scenarios = parent::scenarios();
        $scenarios['search'] = ['id', 'internal_name', 'sort_order', 'is_auto_added', 'name', 'is_deleted'];
        return $scenarios;
    }

    /**
     * Relation to pivot table
     * @return \yii\db\ActiveQuery
     */
    public function getGroupProperties()
    {
        return $this
            ->hasMany(
                PropertyPropertyGroup::className(),
                [
                    'property_group_id' => 'id',
                ]
            )
            ->orderBy(
                [
                    'sort_order_group_properties' => SORT_ASC,
                ]
            );
    }

    /**
     * Relation to properties through pivot relation groupProperties
     * @return \yii\db\ActiveQuery
     */
    public function getProperties()
    {
        return $this
            ->hasMany(
                Property::className(),
                [
                    'id' => 'property_id',
                ]
            )
            ->via('groupProperties');
    }

    /**
     * Performs afterSave event and makes other stuff:
     * - invalidates needed cache
     * - automatically adds bindings if this group is_auto_added
     *
     * @param bool $insert
     * @param array $changedAttributes
     */
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes);

        $this->invalidatePropertyIds();

        if (array_key_exists('is_auto_added', $changedAttributes) && $this->is_auto_added === true) {
            $this->autoAddToObjects();
        }
    }

    /**
     * Invalidates cache of property ids sequence
     */
    public function invalidatePropertyIds()
    {
        Yii::$app->cache->delete("PropertyIdsForGroup:{$this->id}");
        Yii::$app->cache->delete("AutoAddedGroupsIds:{$this->applicable_property_model_id}");
    }

    /**
     * @return array
     */
    public function getAttributeLabels()
    {
        return [
            'id' => Module::t('app', 'ID'),
            'internal_name' => Module::t('app', 'Internal Name'),
            'sort_order' => Module::t('app', 'Sort Order'),
            'applicable_property_model_id' => Module::t('app', 'Applicable Property Model ID'),
            'is_auto_added' => Module::t('app', 'Is Auto Added'),
            'name' => Module::t('app', 'Name'),
        ];
    }

    /**
     * Finds new applicable models and binds this group to them
     *
     * @throws \yii\db\Exception Database exception is thrown on error
     */
    public function autoAddToObjects()
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $modelClassName */
        $modelClassName = PropertiesHelper::classNameForApplicablePropertyModelId($this->applicable_property_model_id);
        $modelIdsWithoutGroup = $modelClassName::find()
            ->leftJoin(
                $modelClassName::bindedPropertyGroupsTable() . ' bpg',
                'bpg.model_id = id'
            )
            ->where('bpg.model_id IS NULL')
            ->select('id')
            ->column($modelClassName::getDb());

        $insertRows = array_map(
            function ($item) {
                return [
                    $item,
                    $this->id
                ];
            },
            $modelIdsWithoutGroup
        );
        if (count($insertRows) > 0) {
            $modelClassName::getDb()->createCommand()->batchInsert(
                $modelClassName::bindedPropertyGroupsTable(),
                [
                    'model_id',
                    'property_group_id',
                ],
                $insertRows
            )->execute();
        }
    }

    /**
     * Returns property ids sequence array for $property_group_id, uses cache
     *
     * @param int $propertyGroupId
     *
     * @return int[] Array of property ids for group
     */
    public static function propertyIdsForGroup($propertyGroupId)
    {
        if (isset(static::$groupIdToPropertyIds[$propertyGroupId])) {
            return static::$groupIdToPropertyIds[$propertyGroupId];
        }
        $ids = Yii::$app->cache->lazy(
            function () use ($propertyGroupId) {
                $query = new Query();
                return array_map(
                    function ($item) {
                        return intval($item);
                    },
                    $query
                        ->select(['property_id'])
                        ->from(PropertyPropertyGroup::tableName())
                        ->where(['property_group_id' => $propertyGroupId])
                        ->orderBy(['sort_order_group_properties' => SORT_ASC])
                        ->column(static::getDb())
                );

            },
            "PropertyIdsForGroup:$propertyGroupId",
            86400,
            [
                NamingHelper::getObjectTag(PropertyGroup::class, $propertyGroupId),
            ]
        );
        static::$groupIdToPropertyIds[$propertyGroupId] = $ids;
        return static::$groupIdToPropertyIds[$propertyGroupId];
    }

    /**
     * Returns auto-added property group ids for specified $applicablePropertyModelId
     *
     * @param integer $applicablePropertyModelId
     *
     * @return integer[]
     */
    public static function getAutoAddedGroupsIds($applicablePropertyModelId)
    {
        return Yii::$app->cache->lazy(
            function () use ($applicablePropertyModelId) {
                return array_map(
                    function ($item) {
                        return intval($item);
                    },
                    PropertyGroup::find()
                        ->select('id')
                        ->where(['applicable_property_model_id' => $applicablePropertyModelId, 'is_auto_added' => 1])
                        ->orderBy(['sort_order' => SORT_ASC])
                        ->column()
                );
            },
            'AutoAddedGroupsIds:' . $applicablePropertyModelId,
            86400 // @todo Add dependencies
        );
    }

    /**
     * Returns ActiveDataProvider for searching PropertyGroup models
     *
     * @param integer $applicablePropertyModelId Applicable property model id
     * @param array $params Array of filter params
     *
     * @return \yii\data\ActiveDataProvider
     *
     * @codeCoverageIgnore
     */
    public function search($applicablePropertyModelId, $params, $showHidden = false)
    {
        $query = self::find()
            ->where([
                'applicable_property_model_id' => $applicablePropertyModelId,
            ]);

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
        $query->andFilterWhere(['like', 'internal_name', $this->internal_name]);
        $query->andFilterWhere(['is_auto_added' => $this->is_auto_added]);
        $query->andFilterWhere(['is_deleted' => $this->is_deleted]);
        // filter by multilingual field
        $query->andFilterWhere(['like', 'property_group_translation.name', $this->name]);

        return $dataProvider;
    }

    public function validateApplicablePropertyMode($attribute, $params)
    {
        return PropertiesHelper::classNameForApplicablePropertyModelId($this->$attribute) !== false;
    }

//    public function link($name, $model, $extraColumns = [])
//    {
//        parent::link($name, $model, $extraColumns);
//        $this->invalidatePropertyIds();
//    }
//
//    public function unlink($name, $model, $delete = false)
//    {
//        parent::unlink($name, $model, $delete);
//        $this->invalidatePropertyIds();
//    }
}
