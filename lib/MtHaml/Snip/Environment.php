<?php

namespace MtHaml\Snip;

use MtHaml\Exception;
use MtHaml\Snip\Exception\SnipException;
use MtHaml\Snip\NodeVisitor\ApplySnip;
use MtHaml\Snip\Target\PhpSnip;
use MtHaml\Target\Php;
use MtHaml\Target\Twig;
use MtHaml\NodeVisitor\Escaping as EscapingVisitor;
use MtHaml\Snip\NodeVisitor\ApplyPlaceholderValue;
use MtHaml\Snip\NodeVisitor\MakesurePlaceholderValue;
use MtHaml\Snip\Snip\SnipHouse;
use MtHaml\Snip\Log\Log;
use MtHaml\Snip\Log\LogInterface;

class Environment extends \MtHaml\Environment
{
    protected $snipHouse;

    public function __construct($target, array $options = array())
    {
        if (isset($options['log']))
            $this->setLog($options['log']);

        if (isset($options['placeholdervalues']))
            $this->setPlaceholdervalues($options['placeholdervalues']);

        //  see README.md : Development Rool 2
        $options = $options + array(
                'filename' => '',
                'uses' => array(),
                'snipname' => '',
                'placeholdervalues' => null,
                'prepare' => false,
                'baseIndent' => 0,
                'level' => 0,
                'log' => null,
                'debug' => false,
                'snipcallerNode'=>null,
                'parentenv'=>null,
            );

        parent::__construct($target, $options);
    }

    public function compileString($string, $filename,$returnRoot=false)
    {
        $prepareWork = false;
        if ($this->getOption('prepare')) {
            list($string, $filename, $prepareWork) = $this->prepare($string, $filename);
        }

        $string = $this->parseInlineSnipCaller($string);
        $string = $this->parseInlinePlaceholder($string);

        if ($returnRoot){
            // copied from parent::compileString
            // run until PhpRenderer
            $target = $this->getTarget();
            $node = $target->parse($this, $string, $filename);
            foreach($this->getVisitors() as $visitor) {
                $node->accept($visitor);
            }
            $compiled = $node;
        }else{
            $compiled = parent::compileString($string, $filename);
        }

        if ($prepareWork && !$this->getOption('debug')) {
            unlink($filename);
        }
        return $compiled;

    }

    protected function prepare($string, $filename)
    {
        $prepareWork = false;

        //  There seems to be some unexpected behavior when using the /m modifier when the line terminators are win32 or mac format.
        //  http://www.php.net/manual/en/function.preg-replace.php#85416
        $string = str_replace(array("\r\n", "\r"), "\n", $string);

        // parse {= =} and {% %} , and protect <?php which maybe used by snips
        $changed = preg_replace(array(
                '/<\?php\s/',
                '/\{=\s*([^}]+)\s*=\}/',
                '/^\{%\s*([^}]+)\s*%\}$/m',
            ),
            array(
                '<<<php',
                '<?php echo \1; ?>',
                '<?php \1; ?>',
            ), $string, -1, $count);
        if ($count > 0) {
            $prepareWork = true;
            $filename = $filename . '.prepare.haml';
            file_put_contents($filename, $changed);
            ob_start();
            try {
                include $filename;
                $string = ob_get_clean();
            } catch (\Exception $e) {
                ob_end_clean();
                throw new  Exception("prepare file $filename : $e");
            }

            // restore <?php
            $string = str_replace('<<<php', '<?php ', $string);


        }

        return array($string, $filename, $prepareWork);

    }

    /* @{} -> X
     * \@{} -> @{}
     * \\@{} -> \X
     * \\\@{} -> \@{}
     * \\\\@{} -> \\X
     * \\\\\@{} -> \\@{}
     */
    protected function parseInlineSnipCaller($string)
    {
        return preg_replace_callback('/(?<escape>\\\\*)@\{(\w+)\}/', function ($matches) {
            $number = strlen($matches['escape']);
            if ($number % 2 == 0) {
                $front = str_repeat('\\', $number / 2);
                $options = array(
                    'level' => $this->getOption('level') + 1,
                );
                // trim any break line or indent space
                $parsedSnip =  rtrim(
                        self::parseSnip($matches[2], array(), $options, $this),
                        "\n") ;
                return $front . $parsedSnip;
            } else {
                return str_repeat('\\', ($number - 1) / 2) . '@{' . $matches[2] . '}';
            }
        }, $string);

    }

    protected function parseInlinePlaceholder($string)
    {

        if ($this->hasPlaceholdervalues()) {
            $values = $this->getPlaceholdervalues();
            $nextMaybeUnnamedPlaceholderIndex = 0;

            $string = preg_replace_callback(
                '/
                (?<block>
                    (?m:
                        ^
                        \s*
                        @@@
                        (?:\s*|\s+.*)
                        $ # un-named Placeholder
                     )
                 )
                |
                (?:
                    (?<escape>\\\\*)
                    \{@
                        (
                            :
                            (?<default>.*)
                         )?
                     @\}
                  ) # un-named InlinePlaceholder
                |
                (?:
                    (?<escape2>\\\\*)
                    \{@
                        (
                            (?<name>\w+)
                            (
                                :
                                (?<default2>.*)
                             )?
                         )?
                   @\}
                ) # named InlinePlaceholder
                /x',
                function ($matches) use (&$values, &$nextMaybeUnnamedPlaceholderIndex) {
                    //  un-named Placeholder
                    if (!empty($matches['block'])) {
                        ++$nextMaybeUnnamedPlaceholderIndex;
                        return $matches['block'];
                    }

                    //  un-named InlinePlaceholder
                    if (empty($matches['name'])) {
                        $number = isset($matches['escape'])?strlen($matches['escape']):0;
                        if ($number % 2 == 0) {
                            $front = str_repeat('\\', $number / 2);
                            $name = $nextMaybeUnnamedPlaceholderIndex;
                            if (isset($values[1][$name])) {
                                list($v) = array_splice($values[1], $name, 1);
                                return $front . $this->renderSnipTree($v);
                            } elseif (!empty($matches['default']))
                                return $front . $matches['default'];
                        } else {
                            return str_repeat('\\', ($number - 1) / 2) . '{@' . (array_key_exists(3,$matches)?$matches[3]:'') . '@}';
                        }
                        //  named InlinePlaceholder
                    } else {
                        $number = isset($matches['escape2'])?strlen($matches['escape2']):0;
                        if ($number % 2 == 0) {
                            $front = str_repeat('\\', $number / 2);
                            $name = $matches['name'];
                            if (isset($values[0][$name])) {
                                return $front . $this->renderSnipTree($values[0][$name]);
                            } elseif (!empty($matches['default2'])) {
                                return $front . $matches['default2'];
                            }
                        } else {
                            return str_repeat('\\', ($number - 1) / 2) . '{@' . (array_key_exists(6,$matches)?$matches[6]:'') . '@}';
                        }
                    }

                    throw new SnipException('plz supply value for inlinePlaceholder ' . $name);
                }, $string);

            $this->setPlaceholdervalues($values);
        }

        return $string;
    }

    protected function renderSnipTree($nodes,$trim=true)
    {

        if (!is_array($nodes))
            $nodes = array($nodes);

        $target = $this->getTarget();

        $outputs = array();

        foreach ($nodes as $node) {
            foreach ($this->getVisitors() as $visitor) {
                $node->accept($visitor);
            }

            $outputs[] = $target->compile($this, $node);
        }

        $outputs = implode('', $outputs);
        return $trim? ltrim(rtrim($outputs,"\n")) :outputs;
    }

    function getLog()
    {
        if (empty($this->options['log'])) {
            $log = new Log($this->options['debug']);
            $this->setLog($log);
        }
        $log = $this->options['log'];
        return $log;
    }

    function setLog(LogInterface $log)
    {
        $this->options['log'] = $log;
    }

    protected function snipsReady()
    {
        return $this->snipHouse instanceof SnipHouse;
    }

    function getSnipHouse()
    {
        if (!$this->snipsReady()) {
            $this->setSnipHouse($this->options['uses'], $this->options['filename']);
        }
        return $this->snipHouse;
    }

    protected function setSnipHouse($S, $mainFile)
    {
        // instanceof is nessesary, because Environment maybe instansed in NodeVisitor\PhpRenderer
        if ($S instanceof SnipHouse) {
        } elseif (gettype($S) == 'string' || gettype($S) == 'array'){
            $S = new SnipHouse($S, $mainFile);
        }else{
            throw new SnipException('require str or array or SnipHouse instance to setSnips');
        }
        $this->snipHouse = $S;
    }

    function hasPlaceholdervalues()
    {
        return !empty($this->options['placeholdervalues']);
    }

    function getPlaceholdervalues()
    {
        return $this->options['placeholdervalues'];
    }

    /*
     * @param $v :array(array namedValues, array unnamedValues)
     */
    function setPlaceholdervalues(array $v)
    {
        if(count($v) == 2)
            $this->options['placeholdervalues'] = $v;
        else{
            throw new SnipException(sprintf("there should two elements in array %s to set placeholder values",print_r($v,true)));
            }
    }


    public function getOptions()
    {
        return $this->options;
    }

    public function getTarget()
    {
        $target = $this->target;
        if (is_string($target)) {
            switch ($target) {
                case 'phpsnip':
                    $target = new PhpSnip;
                    break;
                case 'php':
                    $target = new Php;
                    break;
                case 'twig':
                    $target = new Twig;
                    break;
                default:
                    throw new Exception(sprintf('Unknown target language: %s', $target));
            }
            $this->target = $target;
        }
        return $target;
    }

    public function getVisitors()
    {
        $visitors = array();

        // visitor order is important
        $visitors[] = $this->getMakesurePlaceholderValueVisitor();
        // if is useless and harmful, because ApplyPlaceholderValueVisitor also apply placehodler default value
        //  if($this->hasPlaceholdervalues())
        $visitors[] = $this->getApplyPlaceholderValueVisitor($this->getPlaceholdervalues());
        $visitors[] = $this->getApplySnipVisitor($this->getOption('baseIndent'));

        $visitors[] = $this->getAutoclosevisitor();
        $visitors[] = $this->getAutoclosevisitor();
        $visitors[] = $this->getMidblockVisitor();
        $visitors[] = $this->getMergeAttrsVisitor();

        if ($this->getOption('enable_escaper')) {
            $visitors[] = $this->getEscapingVisitor();
        }

        return $visitors;
    }

    public function getApplySnipVisitor($indent)
    {
        return new ApplySnip($indent);
    }
    public function getApplyPlaceholderValueVisitor($values)
    {
        return new ApplyPlaceholderValue($values);
    }

    public function getMakesurePlaceholderValueVisitor()
    {
        return new MakesurePlaceholderValue();
    }

/*
 * @param options:
             array(
                'placeholdervalues' => array(array(),array()),
                'baseIndent' => 0,
                'level' => 0,
            )
* @param $parentEnv : the key reason of this argument is at README.md::Development Rule 2.3.3
*/
    public static function parseSnip($snipName, array $attributes = array(), $options = array(), Environment $parentEnv,$returnRoot=false)
    {


        $options = $options + array(
                'placeholdervalues' => array(array(),array()),
                'baseIndent' => 0,
                'level' => 0,
            );

        $snipHouse = $parentEnv->getSnipHouse();
        $log = $parentEnv->getLog();
        $front = str_repeat("....", $options['level'] - 1);
        $log->info($front . 'HAML : ' . $parentEnv->getOption('filename'));
        $log->info($front . "call snip : [$snipName]");
        list($snipHaml, $fileName, $snips) = $snipHouse->getSnipAndFiles($snipName, $attributes);
        $log->info($front . "located at file $fileName");
        $log->info();


        $haml = new Environment('phpsnip',
            array(
                'snipname' => $snipName,
                'uses' => $snips,
                'filename' => $fileName,
                'prepare' => false,
                'parentenv'=> $parentEnv,
            )
            + $options
            + $parentEnv->getOptions()
        );

        return $haml->compileString($snipHaml, $fileName, $returnRoot);


    }
}
