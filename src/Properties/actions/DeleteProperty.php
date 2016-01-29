<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\Property;
use Yii;
use yii\web\NotFoundHttpException;

class DeleteProperty extends BaseAdminAction
{
    public $listPropertyGroupsActionId = 'list-group-properties';

    public function run($id, $propertyGroupId)
    {
        /** @var Property $model */
        $model = Property::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException("Property model with specified id not found")
        );
        if ($model->delete() !== false) {
            Yii::$app->session->setFlash('warning', Yii::t('app', 'Property has been deleted.'));
        }
        return $this->controller->redirect(
            [
                $this->listPropertyGroupsActionId,
                'id' => $propertyGroupId,
            ]
        );
    }
}
