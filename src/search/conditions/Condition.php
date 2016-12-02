<?php

namespace DevGroup\DataStructure\search\conditions;

abstract class Condition
{
    /** @var string */
    public $tableName;

    /** @var string */
    public $field;

    public $expression;
}
