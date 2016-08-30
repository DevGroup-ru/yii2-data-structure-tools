<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\AdminUtils\traits\BackendRedirect;
use DevGroup\DataStructure\models\StaticValue;
use DevGroup\DataStructure\Properties\Module;
use Yii;
use yii\web\ForbiddenHttpException;
use yii\web\NotFoundHttpException;

class EditStaticValue extends BaseAdminAction
{
    use BackendRedirect;

    public $viewFile = 'edit-static-value';

    const EVENT_BEFORE_INSERT = 'static-value-before-insert';
    const EVENT_BEFORE_UPDATE = 'static-value-before-update';
    const EVENT_AFTER_INSERT = 'static-value-after-insert';
    const EVENT_AFTER_UPDATE = 'static-value-after-update';

    const EVENT_FORM_BEFORE_SUBMIT = 'static-value-form-before-submit';
    const EVENT_FORM_AFTER_SUBMIT = 'static-value-form-after-submit';

    const EVENT_BEFORE_FORM = 'static-value-before-form';
    const EVENT_AFTER_FORM = 'static-value-after-form';

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

        $post = Yii::$app->request->post();
        $canSave = Yii::$app->user->can('dst-property-group-edit');
        if (false === empty($post) && false === $canSave) {
            throw new ForbiddenHttpException(
                Yii::t('yii', 'You are not allowed to perform this action.')
            );
        }
        if (Yii::$app->request->isPost && $model->load($post)) {
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
            'canSave' => $canSave,
        ]);
    }

    public function redirect($url)
    {
        return $this->controller->redirect($url);
    }
}
