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
    private static $languages = [];

    private static $current;

    /**
     * @return array
     */
    public static function getAll()
    {
        if (true === empty(self::$languages)) {
            $langs = Yii::$app->multilingual->getAllLanguages();
            self::$languages = ArrayHelper::map($langs, 'id', 'iso_639_2t');
        }
        return self::$languages;

    }

    /**
     * @return string
     */
    public static function getCurrent()
    {
        if (true === empty(self::$current)) {
            $langs = self::getAll();
            $currentId = Yii::$app->multilingual->language_id;
            self::$current = isset($langs[$currentId]) ? $langs[$currentId] : 'eng';
        }
        return self::$current;
    }
}