<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\web\NotFoundHttpException;

/**
 * Class DeletePropertyGroup
 * @package DevGroup\DataStructure\Properties\actions
 */
class DeletePropertyGroup extends BaseAdminAction
{
    /**
     * @var string
     */
    public $listPropertyGroupsActionId = 'list-property-groups';

    /**
     * Deletes PropertyGroup model and redirects back to property group list
     *
     * @param integer $id
     * @param integer $applicablePropertyModelId
     * @param bool|false $hard
     * @return \yii\web\Response
     * @throws \Exception
     */
    public function run($id, $applicablePropertyModelId, $hard = false)
    {
        /** @var PropertyGroup $model */
        $model = PropertyGroup::loadModel(
            $id,
            false,
            true,
            86400,
            new NotFoundHttpException("PropertyGroup model with specified id not found")
        );

        if ($hard === false) {
            (boolval($model->delete()) === false && $model->isDeleted() === true) ?
                Yii::$app->session->setFlash('info', Module::t('app', 'Property group has been hidden.')) :
                Yii::$app->session->setFlash('warning', Module::t('app', 'Property group  has not been hidden.'));
        } elseif ((bool)$hard === true) {
            $model->hardDelete() !== false ?
                Yii::$app->session->setFlash('danger', Module::t('app', 'Item has been deleted.')) :
                Yii::$app->session->setFlash('warning', Module::t('app', 'Item has not been deleted.'));
        }

        return $this->controller->redirect(
            [
                $this->listPropertyGroupsActionId,
                'applicablePropertyModelId' => $applicablePropertyModelId,
            ]
        );
    }
}
