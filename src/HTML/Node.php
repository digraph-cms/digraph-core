<?php
namespace DigraphCMS\HTML;

abstract class Node {
    protected $hidden = false;

    abstract public function toString():string;

    public function __toString()
    {
        if ($this->hidden()) {
            return '';
        }
        static::loadAssets();
        return $this->toString();
    }

    public function hidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @param boolean $hidden
     * @return $this
     */
    public function setHidden(bool $hidden)
    {
        $this->hidden = $hidden;
        return $this;
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