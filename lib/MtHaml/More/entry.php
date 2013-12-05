<?php
use MtHaml\More\Environment;

require_once __DIR__ . '/../../../vendor/autoload.php';


// MtHamlMore special options:  ( prepare and debug are optional)
//    array(
//        'filename'=>__DIR__.'/php.haml',
//        'uses'=>array(__DIR__.'/snips/php.php'),
//        'prepare'=>true,
//        'debug'=>true,
//    )
function compilePhpMoreHaml($hamlstr,$options)
{
    $env = new Environment('php_more',$options);
    return $env->compileString($hamlstr, isset($options['filename'])?$options['filename']:'[unnamed]');
}

