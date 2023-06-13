<?php

namespace DigraphCMS\Media;

use DigraphCMS\Context;
use DigraphCMS\URL\URL;
use ScssPhp\ScssPhp\Formatter\OutputBlock;

// TODO: figure out another way to extend the scss compiler
// @phpstan-ignore-next-line
class ScssCompiler extends \ScssPhp\ScssPhp\Compiler
{
    function compileImport($rawPath, OutputBlock $out, $once = false)
    {
        if (substr($rawPath[2][0], 0, 1) != '/') {
            $rawPath[2][0] = '/' . Context::url()->route() . '/' . $rawPath[2][0];
        }
        Context::beginUrlContext(new URL($rawPath[2][0]));
        $return = parent::compileImport($rawPath, $out, $once);
        Context::end();
        return $return;
    }
}
