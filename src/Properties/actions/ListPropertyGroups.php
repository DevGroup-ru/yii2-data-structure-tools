<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\PropertyGroup;
use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;
use yii\web\ServerErrorHttpException;

/**
 * Action that displays tabs by applicable property model with grid of all it's property groups.
 *
 * @package DevGroup\DataStructure\Properties\actions
 */
class ListPropertyGroups extends BaseAdminAction
{
    public $viewFile = 'list-property-groups';

    public $editPropertyGroupActionId = 'edit-property-group';

    /**
     * Runs action
     *
     * @param null $applicablePropertyModelId
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     * @throws \yii\web\ServerErrorHttpException
     */
    public function run($applicablePropertyModelId = null)
    {
        $applicablePropertyModels = $this->applicablePropertyModels();

        $currentApplicablePropertyModel =
            isset($applicablePropertyModels[$applicablePropertyModelId]) ?
                $applicablePropertyModels[$applicablePropertyModelId] :
                reset($applicablePropertyModels);

        if ($currentApplicablePropertyModel === false) {
            if ($applicablePropertyModelId === null) {
                throw new ServerErrorHttpException("You should have at least 1 applicable_property_models row");
            } else {
                throw new NotFoundHttpException("ApplicablePropertyModels row not found for specified id");
            }
        }

        $model = new PropertyGroup();
        $model->setScenario('search');
        $params = Yii::$app->request->get();
        $dataProvider = $model->search($currentApplicablePropertyModel['id'], $params);

        return $this->render([
            'applicablePropertyModels' => $applicablePropertyModels,
            'currentApplicablePropertyModel' => $currentApplicablePropertyModel,
            'dataProvider' => $dataProvider,
            'model' => $model,
            'editPropertyGroupActionId' => $this->editPropertyGroupActionId,
        ]);
    }

    /**
     * Returns applicable property models rows indexed by id and containing class_name and name.
     * The difference from PropertiesHelper::applicablePropertyModels is in array structure and having name field.
     *
     * @return array
     */
    private function applicablePropertyModels()
    {
        return Yii::$app->cache->lazy(function () {
            $query = new Query();
            $rows = $query
                ->select(['id', 'class_name', 'name'])
                ->from('{{%applicable_property_models}}')
                ->indexBy('id')
                ->all();

            array_walk($rows, function (&$item) {
                $item['id'] = intval($item['id']);
            });
            return ArrayHelper::map(
                $rows,
                'id',
                function ($item) {
                    return [
                        'class_name' => $item['class_name'],
                        'name' => $item['name'],
                        'id' => $item['id'],
                    ];
                }
            );
        }, 'ApplicablePropertyModelsList', 86400);
    }
}
