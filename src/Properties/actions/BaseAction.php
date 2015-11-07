<?php

namespace DevGroup\DataStructure\Properties\actions;

use Yii;
use yii\base\Action;

/**
 * Class BaseAction represents base action for properties module.
 *
 * @package DevGroup\DataStructure\Properties\actions
 */
class BaseAction extends Action
{
    public $viewFile = 'undefind-view-file';

    /**
     * Renders a view
     *
     * @param array  $params params for render-view
     *
     * @return string result of the rendering
     */
    protected function render($params)
    {
        return $this->controller->render($this->viewFile, $params);
    }
}
