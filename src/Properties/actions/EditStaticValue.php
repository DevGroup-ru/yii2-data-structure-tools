<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\web\NotFoundHttpException;

class EditStaticValue extends BaseAdminAction
{
    public $viewFile = 'edit-static-value';

    public function run($property_id, $id = null, $return_url = null)
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
                Yii::$app->session->setFlash('success', Module::t('app', 'Static value saved.'));

                return $this->controller->redirect([
                    'edit-static-value',
                    'property_id' => $model->property_id,
                    'id' => $model->id,
                    'return_url' => $return_url
                ]);
            }
        }

        return $this->render([
            'model' => $model,
        ]);
    }
}
