<?php

namespace DevGroup\DataStructure\Properties\validators;

use yii\validators\Validator;
use DevGroup\DataStructure\Properties\Module;

/**
 * Class ValuesValidator
 *
 * Multi level array validator
 * EAV translatable values often should looks like this:
 * [
 *      1 => [
 *          0 => 'value one for language with id 1'
 *          1 => 'value two for language with id 1'
 *          2 => 'value three for language with id 1'
 *      ],
 *      2 => [
 *          0 => 'value one for language with id 2'
 *          1 => 'value two for language with id 2'
 *          2 => 'value three for language with id 2'
 *      ]
 * ]
 *
 * @package DevGroup\DataStructure\Properties\validators
 */
class ValuesValidator extends Validator
{
    /**
     * @inheritdoc
     */
    public function validateAttribute($model, $attribute)
    {
        $values = $model->$attribute;
        if (true === is_array($values)) {
            foreach ($values as $row) {
                if (true === is_array($row)) {
                    foreach ($row as $value) {
                        if (false === is_string($value)) {
                            $model->addError($attribute, Module::t('app', 'Value must be a string!'));
                        }
                    }
                }
            }
        }
    }
}