<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyGroup;
use Yii;
use yii\web\NotFoundHttpException;

class EditProperty extends BaseAction
{
    public $listGroupPropertiesActionId = 'list-group-properties';
    public $listPropertyGroupsActionId = 'list-property-groups';
    public $viewFile = 'edit-property';

    /**
     * Runs action
     * @param             $propertyGroupId
     * @param null|string $id
     *
     * @return string|\yii\web\Response
     * @throws bool
     */
    public function run($propertyGroupId, $id = null)
    {
        $propertyGroup = PropertyGroup::loadModel(
            $propertyGroupId,
            false,
            true,
            86400,
            new NotFoundHttpException("PropertyGroup model with specified id not found")
        );

        /** @var Property $model */
        $model = Property::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException("PropertyGroup model with specified id not found")
        );


        if ($model->isNewRecord === false) {
            // populate translations relation as we need to save all
            $model->translations;
        }


        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            foreach (Yii::$app->request->post('PropertyTranslation', []) as $language => $data) {
                foreach ($data as $attribute => $translation) {
                    $model->translate($language)->$attribute = $translation;
                }
            }

            if ($model->save()) {
                if ($id === null) {
                    // That was new record - link it to property group
                    $propertyGroup->link(
                        'properties',
                        $model,
                        [
                            'sort_order_group_properties' => count($propertyGroup->properties),
                        ]
                    );
                }
                Yii::$app->session->setFlash('success', Yii::t('app', 'PropertyGroup saved.'));

                return $this->controller->redirect([
                    $this->listPropertyGroupsActionId,
                    'id' => $propertyGroup->id,
                ]);
            }
        }

        return $this->render([
            'model' => $model,
            'listGroupPropertiesActionId' => $this->listGroupPropertiesActionId,
            'listPropertyGroupsActionId' => $this->listPropertyGroupsActionId,
            'propertyGroup' => $propertyGroup,
        ]);
    }
}
