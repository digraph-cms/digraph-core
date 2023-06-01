<?php

namespace DigraphCMS\Media;

use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;
use ScssPhp\ScssPhp\Formatter\OutputBlock;

class ScssCompiler extends \ScssPhp\ScssPhp\Compiler
{
    function compileImport($rawPath, OutputBlock $out, $once = false)
    {
        if (substr($rawPath[2][0], 0, 1) != '/') {
            $rawPath[2][0] = '/' . URLs::context()->route() . '/' . $rawPath[2][0];
        }
        URLs::beginContext(new URL($rawPath[2][0]));
        $return = parent::compileImport($rawPath, $out, $once);
        URLs::endContext();
        return $return;
    }
}
