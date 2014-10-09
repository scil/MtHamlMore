<?php

namespace MtHamlMoreRuntime;

//use MtHaml\Runtime\AttributeInterpolation;
//use MtHaml\Runtime\AttributeList;

class Runtime
{
    static function renderAttribute($name, $value, $bSingleValue, $bNoNestedArray, $charset = false)
    {
        if (!($bSingleValue && $bNoNestedArray)) {
            if ($name == 'class' || $name == 'id') {
                if ($bNoNestedArray) {
                    if ($bSingleValue) {
                        $value = $value[0];
                    } else {
                        $output=[];
                        foreach ($value as $attri) {
                            if (!is_null($attri)) {
                                $output[] = $attri;
                            }
                        }
                        $value = implode($name=='class'?' ':'-', $output);
                    }
                }else{
                    // http://www.cowburn.info/2012/03/17/flattening-a-multidimensional-array-in-php/
                    $output=[];
                    array_walk_recursive($value, function ($current) use (&$output) {
                        if(!is_null($current))
                            $output[] = $current;
                    });
                    $value = implode($name=='class'?' ':'-', $output);

                }
            }
        }
        if ($value)
            echo ' ', $name, '="', $charset ? htmlspecialchars($value, ENT_QUOTES, $charset) : $value, '"';
    }


}

