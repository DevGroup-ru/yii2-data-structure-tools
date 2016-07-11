<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Class DeleteProperty
 * @package DevGroup\DataStructure\Properties\actions
 */
class DeleteProperty extends BaseAdminAction
{
    /**
     * @var string
     */
    public $listPropertyGroupsActionId = 'list-group-properties';

    /**
     * @param $id
     * @param $propertyGroupId
     * @param bool|false $hard
     * @return \yii\web\Response
     * @throws \Exception
     * @throws bool
     */
    public function run($id, $propertyGroupId, $hard = false)
    {
        /** @var Property $model */
        $model = Property::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException("Property model with specified id not found")
        );

        if ($hard === false) {
            ($model->delete() !== false && $model->isDeleted() === true) ?
                Yii::$app->session->setFlash('info', Module::t('app', 'Item has been hidden.')) :
                Yii::$app->session->setFlash('warning', Module::t('app', 'Item has not been hidden.'));
        } elseif ((bool)$hard === true) {
            $model->hardDelete() !== false ?
                Yii::$app->session->setFlash('danger', Module::t('app', 'Item has been deleted.')) :
                Yii::$app->session->setFlash('warning', Module::t('app', 'Item has not been deleted.'));
        }

        return $this->controller->redirect(
            [
                $this->listPropertyGroupsActionId,
                'id' => $propertyGroupId,
            ]
        );
    }
}
