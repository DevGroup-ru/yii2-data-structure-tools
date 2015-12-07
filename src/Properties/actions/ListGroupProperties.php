<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Action that displays property group properties.
 *
 * @package DevGroup\DataStructure\Properties\actions
 */
class ListGroupProperties extends BaseAdminAction
{
    public $viewFile = 'list-group-properties';

    public $editPropertyGroupActionId = 'edit-property-group';
    public $listPropertyGroupsActionId = 'list-property-groups';

    public $editPropertyActionId = 'edit-property';

    /**
     * Runs action
     *
     * @param integer $id id of property group model
     *
     * @return string
     * @throws \yii\web\NotFoundHttpException
     */
    public function run($id)
    {
        $propertyGroup = PropertyGroup::loadModel(
            $id,
            false,
            true,
            86400,
            new NotFoundHttpException("Property group with specified id not found")
        );

        $model = new Property();
        $model->setScenario('search');
        $params = Yii::$app->request->get();
        $dataProvider = $model->search($propertyGroup->id, $params);

        return $this->render([
            'propertyGroup' => $propertyGroup,
            'dataProvider' => $dataProvider,
            'model' => $model,
            'editPropertyGroupActionId' => $this->editPropertyGroupActionId,
            'listPropertyGroupsActionId' => $this->listPropertyGroupsActionId,
            'editPropertyActionId' => $this->editPropertyActionId,
        ]);
    }
}
