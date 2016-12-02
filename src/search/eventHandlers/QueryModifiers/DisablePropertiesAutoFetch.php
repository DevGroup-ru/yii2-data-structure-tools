<?php

namespace DevGroup\DataStructure\search\eventHandlers\QueryModifiers;

use DevGroup\DataStructure\search\base\SearchEvent;
use DevGroup\DataStructure\traits\PropertiesTrait;
use yii;

class DisablePropertiesAutoFetch
{
    public static function modify(SearchEvent $e)
    {
        $searchQuery = $e->searchQuery();
        if ($searchQuery->fillProperties === false) {
            return;
        }

        $mainEntityClassName = $searchQuery->mainEntityClassName;

        if (in_array(PropertiesTrait::class, class_uses($mainEntityClassName), true)) {
            yii\base\Event::on(
                $mainEntityClassName,
                yii\db\ActiveRecord::EVENT_INIT,
                [static::class, 'autoFetchProperties'],
                true
            );
        }
    }

    public static function autoFetchProperties($e)
    {
        /** @var yii\db\ActiveRecord|PropertiesTrait $sender */
        $sender = &$e->sender;
        $sender->autoFetchProperties = false;
    }
}
