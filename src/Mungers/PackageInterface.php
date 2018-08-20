<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Mungers;

interface PackageInterface extends \Flatrr\FlatArrayInterface
{
    public function log($message);
    public function mungeStart(MungerInterface $munger);
    public function mungeFinished(MungerInterface $munger);

    public function skip($name) : bool;
    public function skipGlob(string $name);
    public function resetSkips();

    public function hash(string $name = null) : string;
    public function serialize(string $name = null) : string;
    public function unserialize($serialized, string $name = null);
}
