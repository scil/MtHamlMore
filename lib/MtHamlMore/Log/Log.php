<?php

namespace MtHamlMore\Log;

class Log implements LogInterface
{
    private $enable=true;

    function __construct($enable=true)
    {
        $this->enable($enable);
    }

    public function info($message='', array $context = array())
    {
        if($this->enable)
            echo($message."<br>\n");
    }
    public function enable($e)
    {
        $this->enable=(bool)$e;
    }
}