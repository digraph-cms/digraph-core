<?php
namespace DigraphCMS\HTML;

abstract class Node {
    abstract public function toString():string;
    public function __toString()
    {
        static::loadAssets();
        return $this->toString();
    }

    protected static function doLoadAssets()
    {
        // does nothing, override this method to make a tag load necessary assets when printed
    }

    protected static function loadAssets()
    {
        static $loaded = false;
        if (!$loaded) {
            $loaded = true;
            static::doLoadAssets();
        }
    }
}