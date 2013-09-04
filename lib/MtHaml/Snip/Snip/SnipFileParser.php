<?php
namespace MtHaml\Snip\Snip;

use MtHaml\Snip\Exception\SnipFileParserException;
use MtHaml\Snip\Lib;

/* snip file var name rule:
 not allowed  name :
    this
 special  name :
    __MtHamlSnip_uses
    __MtHamlSnip_mixes
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
        return $this->uses ? Lib::parseFiles($this->uses) : array();
    }

    public function getMixes()
    {
        return $this->mixes ? Lib::parseFiles($this->mixes) : array();
    }
    public static function snipCaller($snip, $arg, $file)
    {
        if (gettype($snip) == 'string') {
        } elseif (is_callable($snip)) {
            try {
                $normal_arr = $arg[0];
                $named_att = $arg[1];
                $snip = self::call_user_func_named_array($snip, $normal_arr, $named_att);
            } catch (\Exception $e) {
                throw new SnipFileParserException($file, $snip, "run wrong $e");
            }
        } else {
            throw new SnipFileParserException($file, $snip, 'not string or callable ');
        }
        return $snip;
    }

    // based on code from: http://blog.creapptives.com/post/26272336268/calling-php-functions-with-named-parameters
    static function call_user_func_named_array($method, $normal_arr, $named_att)
    {
        $ref = new \ReflectionFunction($method);
        $params = [];
        foreach ($ref->getParameters() as $p) {
            if (!isset($named_att[$p->name])) {
                if (empty($normal_arr)) {
                    if ($p->isOptional())
                        $params[] = $p->getDefaultValue();
                    else
                        throw new \Exception("Missing parameter $p->name");
                } else
                    $params[] = array_shift($normal_arr);
            } else {
                $params[] = $named_att[$p->name];
            }
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

        foreach(array('uses','mixes') as $type){
            $v = '__MtHamlSnip_'.$type;
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