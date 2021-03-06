<?php
namespace DevGroup\DataStructure\models;

use arogachev\sortable\behaviors\numerical\ContinuousNumericalSortableBehavior;
use DevGroup\DataStructure\behaviors\PackedJsonAttributes;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\Entity\traits\EntityTrait;
use DevGroup\Entity\traits\SoftDeleteTrait;
use DevGroup\Multilingual\behaviors\MultilingualActiveRecord;
use DevGroup\Multilingual\traits\MultilingualTrait;
use DevGroup\TagDependencyHelper\CacheableActiveRecord;
use DevGroup\TagDependencyHelper\NamingHelper;
use DevGroup\TagDependencyHelper\TagDependencyTrait;
use Yii;
use yii\caching\TagDependency;
use yii\data\ActiveDataProvider;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\ArrayHelper;

/**
 * Class StaticValue
 * @package DevGroup\DataStructure\models
 * @mixin MultilingualActiveRecord
 * @mixin CacheableActiveRecord
 *
 * @property integer $sort_order
 * @property integer $property_id
 * @property string $name
 * @property string $description
 * @property string $slug
 * @property Property $property
 */
class StaticValue extends ActiveRecord
{
    /**
     * @var array
     */
    public static $valuesByPropertyId = [];

    use MultilingualTrait;
    use TagDependencyTrait;
    use EntityTrait;
    use SoftDeleteTrait;


    /**
     * @inheritdoc
     */
    private $rules = [
        [['sort_order', 'property_id'], 'integer',],
        [['params'], 'safe'],
        [['property_id'], 'required',],
        [['slug'], 'default', 'value' => ''],
        [['id'], 'integer', 'on' => 'search'],
        [['name', 'slug', 'is_deleted'], 'safe', 'on' => 'search'],
        [['sort_order', 'property_id'], 'filter', 'filter' => 'intval'],
    ];


    /**
     * @param Property|null $property
     * @param array $config
     */
    public function __construct(Property $property = null, $config = [])
    {
        if ($property !== null) {
            $this->property_id = $property->id;
        }
        parent::__construct($config);
    }


    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'multilingual' => [
                'class' => MultilingualActiveRecord::class,
                'translationPublishedAttribute' => false,
            ],
            'CacheableActiveRecord' => [
                'class' => CacheableActiveRecord::class,
            ],
            'ContinuousNumericalSortableBehavior' => [
                'class' => ContinuousNumericalSortableBehavior::class,
                'sortAttribute' => 'sort_order'
            ],
            'PackedJsonAttributes' => [
                'class' => PackedJsonAttributes::class,
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%static_value}}';
    }

    /**
     * @inheritdoc
     */
    public function getAttributeLabels()
    {
        return [
            'id' => Module::t('app', 'ID'),
            'property_id' => Module::t('app', 'Property ID'),
            'sort_order' => Module::t('app', 'Sort Order'),
            'name' => Module::t('app', 'Name'),
            'description' => Module::t('app', 'Description'),
            'slug' => Module::t('app', 'Slug'),
        ];
    }

    /**
     * Performs beforeSave event
     *
     * @param bool $insert
     *
     * @return bool
     */
    public function beforeSave($insert)
    {
        if (!$this->sort_order) {
            $property = Property::findById($this->property_id);
            $this->sort_order = count(static::valuesForProperty($property));
        }
        return parent::beforeSave($insert);
    }

    /**
     * Returns array of possible values for property.
     * Array consists of arrays with elements: name, description, slug and key is static_value.id
     *
     * @param \DevGroup\DataStructure\models\Property $property
     *
     * @return array
     */
    public static function valuesForProperty(Property $property)
    {
        $propertyId = $property->id;

        $table = static::tableName();
        $translationsTable = StaticValueTranslation::tableName();
        $db = static::getDb();
        $languageId = Yii::$app->multilingual->language_id;

        if (!isset(static::$valuesByPropertyId[$propertyId])) {
            static::$valuesByPropertyId[$propertyId] = Yii::$app->cache->lazy(
                function () use ($propertyId, $table, $translationsTable, $db, $languageId) {
                    $query = new Query();
                    $rows = $query
                        ->select([
                            'sv.id',
                            'svt.name',
                            'svt.description',
                            'svt.slug',
                        ])
                        ->from($table . ' sv')
                        ->where(['sv.property_id' => $propertyId])
                        ->innerJoin(
                            $translationsTable . 'svt',
                            'svt.model_id = sv.id AND svt.language_id=:language_id',
                            [
                                ':language_id' => $languageId,
                            ]
                        )
                        ->orderBy('sv.sort_order ASC')
                        ->all($db);
                    return ArrayHelper::map(
                        $rows,
                        'id',
                        function ($item) {
                            return [
                                'name' => $item['name'],
                                'description' => $item['description'],
                                'slug' => $item['slug'],
                            ];
                        }
                    );
                },
                "StaticValues:$propertyId:$languageId",
                86400,
                "StaticValues:$propertyId"
            );
        }
        return static::$valuesByPropertyId[$propertyId];
    }

    /**
     * @inheritdoc
     */
    public function afterSave($insert, $changedAttributes)
    {
        // clear static cache for current property_id static values
        static::$valuesByPropertyId[$this->property_id] = null;

        if (isset($changedAttributes['property_id'])) {
            // clear old static cache for old property_id static values
            static::$valuesByPropertyId[$changedAttributes['property_id']] = null;
        }

        TagDependency::invalidate(
            Yii::$app->cache,
            [
                'StaticValues:' . $this->property_id
            ]
        );
        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProperty()
    {
        return $this->hasOne(Property::className(), ['id' => 'property_id']);
    }

    /**
     * @param int|null $propertyId
     * @param array $params
     *
     * @return ActiveDataProvider
     * @codeCoverageIgnore
     */
    public function search($propertyId = null, $params = [], $showHidden = false)
    {
        $query = self::find();
        if ($propertyId !== null) {
            $query->where(['property_id' => $propertyId]);
        }


        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 8,
            ],
        ]);
        $dataProvider->sort->attributes['name'] = [
            'asc' => ['static_value_translation.name' => SORT_ASC],
            'desc' => ['static_value_translation.name' => SORT_DESC],
        ];

        $dataProvider->sort->defaultOrder = ['sort_order' => SORT_ASC];

        if (!($this->load($params))) {
            if ($showHidden === false) {
                $this->is_deleted = 0;
                $query->andWhere(['is_deleted' => $this->is_deleted]);
            }
            return $dataProvider;
        }

        // perform filtering
        $query->andFilterWhere(['id' => $this->id]);
        $query->andFilterWhere(['sort_order' => $this->sort_order]);
        $query->andFilterWhere(['is_deleted' => $this->is_deleted]);

        // filter by multilingual field
        $query->andFilterWhere(['like', 'static_value_translation.slug', $this->slug]);
        $query->andFilterWhere(['like', 'static_value_translation.name', $this->name]);


        return $dataProvider;
    }
}
