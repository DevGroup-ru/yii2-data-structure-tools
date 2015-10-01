<?php

namespace DevGroup\DataStructure\propertyStorage;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\StaticValue;
use Yii;

class StaticValues extends AbstractPropertyStorage
{

    /**
     * @inheritdoc
     */
    public function fillProperties(&$models)
    {
        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait|\DevGroup\TagDependencyHelper\TagDependencyTrait $firstModel */
        $firstModel = reset($models);
        $static_values_rows = Yii::$app->cache->lazy(function() use($firstModel) {
            $query = new \yii\db\Query();
            //! @todo join with StaticValue table for knowing property_id
            return $query
                ->select([
                    'model_id',
                    'static_value_id',
                    'sort_order',
                ])
                ->from($firstModel->static_values_bindings_table())
                ->innerJoin(StaticValue::tableName(), ['static_value_id'=>'id'])
                ->where(PropertiesHelper::getInCondition($models))
                ->orderBy(['model_id' => SORT_ASC, 'sort_order' => SORT_ASC])
                ->all($firstModel->getDb());
        }, PropertiesHelper::generateCacheKey($models, 'static_values'), 86400, $firstModel->commonTag());

        // fill models with static values
        $modelIdToArrayIndex = PropertiesHelper::idToArrayIndex($models);

        foreach ($static_values_rows as $row) {
            $model = &$models[$modelIdToArrayIndex[$row['model_id']]];

        }
    }

    /**
     * @inheritdoc
     */
    public function deleteAllProperties(&$models)
    {
        /** @var \yii\db\Command $command */
        if (count($models) === 0) {
            return;
        }

        /** @var \yii\db\ActiveRecord|\DevGroup\DataStructure\traits\PropertiesTrait $firstModel */
        $firstModel = reset($models);

        $command = $firstModel->getDb()->createCommand()
            ->delete($firstModel->static_values_bindings_table(), PropertiesHelper::getInCondition($models));

        $command->execute();
    }
}