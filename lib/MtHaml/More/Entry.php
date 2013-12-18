<?php
namespace MtHaml\More;

class Entry
{
    /*
    * @param: $hamlstr [String] haml string
    * @param: $options [Array] options for MtHaml
    * @param : $moreOptions [Array] options for MtHamlMore . options:
    *    'filename': path str of haml file . important options. Without this, haml str would be parsed like MtHaml ,instend of MtHamlMore
    *    'uses': php files which define snips. you can use * like "snips/*.snip.php"
    *    'prepare' : if enable preppare feature . default value : false
    *    'debug' : if debug is true, the process of snip invoking will be output . default value : false
    *    example :
    *        array(
    *            'filename'=>__DIR__.'/php.haml',
    *            'uses'=>array(__DIR__.'/snips/php.php'),
    *            'prepare'=>true,
    *            'debug'=>true,
    *        )
     */
    static function compilePhpMoreHaml($hamlstr, $options, $moreOptions = null)
    {
        static $env;
        if (!is_null($options))
            $env = new Environment('php_more', $options);
        if ($env)
            return $env->compileString($hamlstr, $moreOptions);
        else {
            throw new Exception\MoreException('plz supply 2nd arg when calling this func first time');
        }
    }

}