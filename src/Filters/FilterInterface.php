<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Filters;

use \Digraph\CMS;

interface FilterInterface
{
    public function __construct(CMS &$cms);
    public function filter(string $text, array $opts = []) : string;
    public function context(string $context = null) : ?string;
}
