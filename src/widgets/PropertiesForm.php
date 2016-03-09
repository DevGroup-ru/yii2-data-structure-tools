<?php

namespace DevGroup\DataStructure\widgets;

use DevGroup\DataStructure\helpers\PropertiesHelper;
use DevGroup\DataStructure\models\Property;
use DevGroup\DataStructure\models\PropertyPropertyGroup;
use DevGroup\DataStructure\Properties\Module;
use DevGroup\DataStructure\propertyHandler\AbstractPropertyHandler;
use DevGroup\DataStructure\traits\PropertiesTrait;
use kartik\select2\Select2;
use rmrevin\yii\fontawesome\component\Icon;
use yii\base\Exception;
use yii\base\Widget;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\web\Application;
use yii\widgets\Pjax;

/**
 * Class PropertiesForm
 * @package DevGroup\DataStructure\widgets
 */
class PropertiesForm extends Widget
{
    /**
     * @var ActiveRecord | PropertiesTrait
     */
    public $model;

    /**
     * @var string view file for widget rendering
     */
    public $viewFile = 'properties-form';

    /**
     * @var string route to "add property group" action
     */
    public $addPropertyGroupRoute = '/properties/manage/add-model-property-group';

    /**
     * @var string route to "delete property group" action
     */
    public $deletePropertyGroupRoute = '/properties/manage/delete-model-property-group';

    /**
     * Build array for tabs.
     * @param array $availableGroups
     * @param array $attachedGroups
     * @return array
     */
    protected function buildTabsArray($availableGroups, $attachedGroups)
    {
        $addGroupItems = [];
        $tabs = [];
        foreach ($availableGroups as $id => $name) {
            if (!in_array($id, $attachedGroups)) {
                $addGroupItems[$id] = $name;
            }
            $isAttached = in_array($id, $attachedGroups);
            if ($isAttached) {
                $content = '';
                /** @var Property[] $properties */
                $properties = Property::find()
                    ->from(Property::tableName() . ' p')
                    ->innerJoin(
                        PropertyPropertyGroup::tableName() . ' ppg',
                        'p.id = ppg.property_id'
                    )
                    ->where(['ppg.property_group_id' => $id])
                    ->all();
                foreach ($properties as $property) {
                    $content .= $property
                        ->handler()
                        ->renderProperty(
                            $this->model, $property,
                            AbstractPropertyHandler::BACKEND_EDIT
                        );
                }
                $tabs[] = [
                    'label' => Html::encode($name)
                        . '&nbsp;'
                        . Html::button(
                            new Icon('close'),
                            [
                                'class' => 'btn btn-danger btn-xs',
                                'data-group-id' => $id,
                                'data-action' => 'delete-property-group',
                            ]
                        ),
                    'content' => $content . Html::hiddenInput(
                            'propertyGroupIds[]',
                            $id,
                            [
                                'class' => 'property-group-ids',
                            ]
                        ),
                ];
            }
        }

        $tabs[] = [
            'label' => Select2::widget([
                'data' => $addGroupItems,
                'name' => 'plus-group',
                'options' => [
                    'placeholder' => Module::t('widget', '(+) Add group'),
                    'style' => [
                        'min-width' => '100px'
                    ]
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]),
            'url' => '#',

        ];

        return $tabs;
    }

    /**
     * @throws Exception
     */
    public function run()
    {
        if (!$this->model instanceof ActiveRecord || !$this->model->hasMethod('ensurePropertyGroupIds')) {
            throw new Exception('Field "model" must be an ActiveRecord and uses a PropertiesTrait');
        }
        PropertiesFormAsset::register($this->getView());
        $availableGroups = PropertiesHelper::getAvailablePropertyGroupsList(get_class($this->model));
        $models = [$this->model];
        PropertiesHelper::fillProperties($models);
        $attachedGroups = $this->getAttachedGroups();
        $tabs = $this->buildTabsArray($availableGroups, $attachedGroups);
        echo $this->render(
            $this->viewFile,
            [
                'attachedGroups' => $attachedGroups,
                'availableGroups' => $availableGroups,
                'tabs' => $tabs,
            ]
        );
    }

    /**
     * @return \integer[]|null
     */
    public function getAttachedGroups()
    {
        return $this->model->propertyGroupIds;
    }

}
