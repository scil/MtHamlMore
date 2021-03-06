<?php
namespace MtHamlMore\Snip;

use MtHamlMore\Exception\SnipFileParserException;
use MtHamlMore\Lib\File;

/* snip file var name rule:
 not allowed  name :
    this
 special  name :
    __MtHamlMore_uses
    __MtHamlMore_mixes
*/

class SnipFileParser implements SnipFileParserInterface
{
    protected $file;
    protected $snips;
    protected $uses;
    protected $mixes;

    public function __construct($file)
    {
        $this->file = $file;
        $this->parse();
    }

    public function getSnips()
    {
        return $this->snips;
    }

    public function getUses()
    {
        return $this->uses ? File::parseFiles($this->uses) : array();
    }

    public function getMixes()
    {
        return $this->mixes ? File::parseFiles($this->mixes) : array();
    }

    public static function snipCaller($snip, $arg, $namedPlaceholderValue, $file)
    {
        if (gettype($snip) == 'string') {
        } elseif (is_callable($snip)) {
            try {
                $normal_arr = $arg[0];
                $named_att = $arg[1];
                $snip = self::call_user_func_named_array($snip, $normal_arr, $named_att, $namedPlaceholderValue);
            } catch (\Exception $e) {
                throw new SnipFileParserException($file, $snip, "run wrong $e");
            }
        } else {
            throw new SnipFileParserException($file, $snip, 'not string or callable ');
        }
        return $snip;
    }

    // based on code from: http://blog.creapptives.com/post/26272336268/calling-php-functions-with-named-parameters
    static function call_user_func_named_array($method, $normal_arr, $named_att, $inlineContent)
    {
        $ref = new \ReflectionFunction($method);
        $params = [];
        foreach ($ref->getParameters() as $p) {
            $name = $p->name;
            if (isset($named_att[$name])) {
                $params[] = $named_att[$name];
            } elseif ($inlineContent &&
                isset($inlineContent[$name]) &&
                is_string($inlineContent[$name])) {
                $params[] = $inlineContent[$name];
            } elseif (!empty($normal_arr)) {
                $params[] = array_shift($normal_arr);
            } elseif  ($p->isOptional()) {
                $params[] = $p->getDefaultValue();
            } else
                throw new \Exception("Missing parameter $p->name");

        }
        return $ref->invokeArgs($params);
    }

    public function parse()
    {
        try {
            ob_start();
            include $this->file;
            ob_end_clean();
        } catch (\Exception $e) {
            throw new SnipFileParserException($this->file, '', "include snip file {$this->file} err: $e");
        }

        foreach (array('uses', 'mixes') as $type) {
            $v = '__MtHamlMore_' . $type;
            if (isset($$v)) {
                if (is_callable($$v)) {
                    try {
                        $$v = $$v();
                    } catch (\Exception $e) {
                        throw new SnipFileParserException($this->file, '', "parse \$\$v wrong $e");
                    }
                }
                $this->$type = explode(';', $$v);
                unset($$v);
            }
        }

        $this->snips = get_defined_vars();
    }


}