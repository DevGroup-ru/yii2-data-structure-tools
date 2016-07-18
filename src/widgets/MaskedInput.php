<?php


namespace DevGroup\DataStructure\widgets;


use yii\helpers\Json;
use yii\web\View;

class MaskedInput extends \yii\widgets\MaskedInput
{
    /**
     * @inheritdoc
     */
    protected function hashPluginOptions($view)
    {
        $encOptions = empty($this->clientOptions) ? '{}' : Json::htmlEncode($this->clientOptions);
        $this->_hashVar = self::PLUGIN_NAME . '_' . hash('crc32', $encOptions);
        $this->options['data-plugin-' . self::PLUGIN_NAME] = $this->_hashVar;
        $view->registerJs("window.{$this->_hashVar} = {$encOptions};", View::POS_READY);
        $view->registerJs(
        /** @lang JavaScript */
            "jQuery('body').on('click', '[data-action=\"add-new-eav-input\"]', function() {
        var input = jQuery(this).parents('.multi-eav').eq(0).find('.input-group').last().find('input');
        Inputmask(window[input.data('plugin-inputmask')]).mask(input);
        return false;
    })",
            View::POS_READY
        );
    }
}