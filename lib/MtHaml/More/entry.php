<?php
namespace MtHaml\More;

use MtHaml\More\Environment;

class Entry
{
// $moreOptions:  ( 'prepare' and 'debug' are optional)
//    array(
//        'filename'=>__DIR__.'/php.haml',
//        'uses'=>array(__DIR__.'/snips/php.php'),
//        'prepare'=>true,
//        'debug'=>true,
//    )
    static function compilePhpMoreHaml($hamlstr, $options, $moreOptions = null)
    {
        static $env;
        if (!is_null($options))
            $env = new Environment('php_more', $options);
        if ($env instanceof Environment)
            return $env->compileString($hamlstr, $moreOptions);
        else {
            throw new \MtHaml\More\Exception\MoreException('plz supply 2nd arg when calling this func first time');
        }
    }

}