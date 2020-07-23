<?php
/* Digraph CMS: Utilities | https://github.com/digraphcms/digraph-utilities | MIT License */
namespace Digraph\Mungers;

interface MungerInterface
{
    public function __construct(string $name);
    public function name(string $name = null, bool $includeParent = true) : string;
    public function parent(MungerInterface $parent = null) : ?MungerInterface;

    public function munge(PackageInterface $package);
}
