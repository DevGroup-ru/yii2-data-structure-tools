<?php

namespace DevGroup\DataStructure\Properties\actions;

use DevGroup\AdminUtils\actions\BaseAdminAction;
use DevGroup\DataStructure\models\PropertyGroup;
use DevGroup\DataStructure\traits\PropertiesTrait;
use Yii;
use yii\db\ActiveRecord;
use yii\web\BadRequestHttpException;

class DeleteModelPropertyGroup extends BaseAdminAction
{
    /**
     * @param string|ActiveRecord $className
     * @param integer $modelId
     * @return bool
     * @throws BadRequestHttpException
     */
    public function run($className, $modelId)
    {
        if (!$groupId = Yii::$app->request->post('groupId')) {
            throw new BadRequestHttpException();
        }
        /** @var PropertiesTrait $model */
        $model = $className::findOne($modelId);
        /** @var PropertyGroup $group */
        $group = PropertyGroup::findOne($groupId);
        return $model->deletePropertyGroup($group);
    }
}
