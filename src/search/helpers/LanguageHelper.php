<?php

namespace DevGroup\DataStructure\search\helpers;

use yii\base\Component;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class LanguageHelper
 *
 * @package DevGroup\DataStructure\search\helpers
 */
class LanguageHelper extends Component
{
    /**
     * @return array
     */
    public static function getAll()
    {
        $langs = Yii::$app->multilingual->getAllLanguages();
        $langs = ArrayHelper::map($langs, 'id', 'iso_639_2t');
        return $langs;

    }

    /**
     * @return string
     */
    public static function getCurrent()
    {
        $langs = self::getAll();
        $currentId = Yii::$app->multilingual->language_id;
        return isset($langs[$currentId]) ? $langs[$currentId] : 'eng';
    }
}