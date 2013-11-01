<?php
use MtHaml\Snip\Environment;

require_once __DIR__ . '/../../../vendor/autoload.php';


// MtHamlSnip special options:
//    array(
//        'uses'=>array(__DIR__.'/snips/php.php'),
//        'prepare'=>true,
//        'debug'=>true,
//        'filename'=>__DIR__.'/php.haml',
//    )
function compilePhpSnipHaml($hamlstr,$options)
{
    $env = new Environment('phpsnip',$options);
    return $env->compileString($hamlstr, isset($options['filename'])?$options['filename']:'[unnamed]');
}

