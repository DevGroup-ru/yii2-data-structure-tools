<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

/**
 * Class EditPropertyGroup
 *
 * @package DevGroup\DataStructure\Properties\actions
 */
class EditPropertyGroup extends BaseAdminAction
{
    public $listPropertyGroupsActionId = 'list-property-groups';
    public $viewFile = 'edit-property-group';

    /**
     * @param $applicablePropertyModelId
     * @param null $id
     * @return string|\yii\web\Response
     * @throws ForbiddenHttpException
     * @throws \Exception
     * @throws bool
     */
    public function run($applicablePropertyModelId, $id = null)
    {
        /** @var PropertyGroup $model */
        $model = PropertyGroup::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException("PropertyGroup model with specified id not found")
        );
        $model->applicable_property_model_id = $applicablePropertyModelId;

        if ($model->isNewRecord === false) {
            // populate translations relation as we need to save all
            $model->translations;
        }
        $post = Yii::$app->request->post();
        $canSave = Yii::$app->user->can('dst-property-group-edit');
        if (false === empty($post) && false === $canSave) {
            throw new ForbiddenHttpException(
                Yii::t('yii', 'You are not allowed to perform this action.')
            );
        }
        if (Yii::$app->request->isPost && $model->load($post)) {
            foreach (Yii::$app->request->post('PropertyGroupTranslation', []) as $language => $data) {
                foreach ($data as $attribute => $translation) {
                    $model->translate($language)->$attribute = $translation;
                }
            }

            if ($model->save()) {
                Yii::$app->session->setFlash('success', Module::t('app', 'PropertyGroup saved.'));

                return $this->controller->redirect([
                    $this->listPropertyGroupsActionId,
                    'applicablePropertyModelId' => $model->applicable_property_model_id,
                ]);
            }
        }
      //$this->notify('test');

        return $this->render([
            'model' => $model,
            'applicablePropertyModelId' => $model->applicable_property_model_id,
            'listPropertyGroupsActionId' => $this->listPropertyGroupsActionId,
            'canSave' => $canSave,
        ]);
    }
}
