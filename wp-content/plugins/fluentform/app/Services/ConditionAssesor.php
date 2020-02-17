<?php

namespace FluentForm\App\Services;

use FluentForm\App\Helpers\Str;
use FluentForm\Framework\Helpers\ArrayHelper;

class ConditionAssesor
{
    public static function evaluate(&$field, &$inputs)
    {
        $conditionals = ArrayHelper::get($field, 'conditionals.status') ?
                        ArrayHelper::get($field, 'conditionals.conditions') : false;

        $hasConditionMet = true;

        if ($conditionals) {
            $toMatch = ArrayHelper::get($field, 'conditionals.type');
            foreach ($conditionals as $conditional) {

                $hasConditionMet = static::assess($conditional, $inputs);
                if($hasConditionMet && $toMatch == 'any') {
                    return true;
                }
                if ($toMatch === 'all' && !$hasConditionMet) {
                    return false;
                }
            }
        }

        return $hasConditionMet;
    }

    public static function assess(&$conditional, &$inputs)
    {
        if ($conditional['field']) {
            $inputValue = ArrayHelper::get($inputs, $conditional['field']);

            switch ($conditional['operator']) {
                case '=':
                    if(is_array($inputValue)) {
                       return in_array($conditional['value'], $inputValue);
                    }
                    return $inputValue === $conditional['value'];
                    break;
                case '!=':
                    if(is_array($inputValue)) {
                        return !in_array($conditional['value'], $inputValue);
                    }
                    return $inputValue !== $conditional['value'];
                    break;
                case '>':
                    return $inputValue > $conditional['value'];
                    break;
                case '<':
                    return $inputValue > $conditional['value'];
                    break;
                case '>=':
                    return $inputValue > $conditional['value'];
                    break;
                case '<=':
                    return $inputValue > $conditional['value'];
                    break;
                case 'startsWith':
                    return Str::startsWith($inputValue, $conditional['value']);
                    break;
                case 'endsWith':
                    return Str::endsWith($inputValue, $conditional['value']);
                    break;
                case 'contains':
                    return Str::contains($inputValue, $conditional['value']);
                    break;
            }
        }

        return false;
    }
}
