<?php
namespace DigraphCMS\HTML;

abstract class Node {
    /** @var bool */
    protected $hidden = false;

    abstract public function toString():string;

    public function __toString(): string
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
     * @return static
     */
    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;
        return $this;
    }

    protected static function doLoadAssets(): void
    {
        // does nothing, override this method to make a tag load necessary assets when printed
    }

    protected static function loadAssets(): void
    {
        static $loaded = false;
        if (!$loaded) {
            $loaded = true;
            static::doLoadAssets();
        }
    }
}