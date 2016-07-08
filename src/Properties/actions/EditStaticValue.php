<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\AdminUtils\traits\BackendRedirect;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\web\NotFoundHttpException;

class EditStaticValue extends BaseAdminAction
{
    use BackendRedirect;

    public $viewFile = 'edit-static-value';

    public function run($property_id, $propertyGroupId, $id = null, $return_url = null)
    {
        /** @var StaticValue $model */
        $model = StaticValue::loadModel(
            $id,
            true,
            true,
            86400,
            new NotFoundHttpException("PropertyGroup model with specified id not found")
        );

        $model->property_id = $property_id;

        if ($model->isNewRecord === false) {
            // populate translations relation as we need to save all
            $model->translations;
        }

        $model->loadDefaultValues();

        if (Yii::$app->request->isPost && $model->load(Yii::$app->request->post())) {
            foreach (Yii::$app->request->post('StaticValueTranslation', []) as $language => $data) {
                foreach ($data as $attribute => $translation) {
                    $model->translate($language)->$attribute = $translation;
                }
            }

            if ($model->save()) {

                return $this->redirectUser(
                    $model->id,
                    true,
                    [
                        'edit-property',
                        'id' => $property_id,
                        'propertyGroupId' => $propertyGroupId
                    ],
                    [
                        'edit-static-value',
                        'property_id' => $property_id,
                        'propertyGroupId' => $propertyGroupId
                    ]
                );
            }
        }

        return $this->render([
            'model' => $model,
        ]);
    }

    public function redirect($url)
    {
        return $this->controller->redirect($url);
    }
}
