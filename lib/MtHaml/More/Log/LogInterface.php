<?php

namespace MtHaml\More\Log;

interface LogInterface
{
     public function info($message, array $context = array());
    public function enable($e);
}