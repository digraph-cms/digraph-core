<?php

namespace DigraphCMS\HTML;

abstract class Tag extends Node
{
    protected $tag, $id;
    protected $attributes = [];
    protected $classes = [];
    protected $data = [];
    protected $children = [];
    protected $void = false;

    public function tag(): string
    {
        return $this->tag;
    }

    public function void(): bool
    {
        return $this->void;
    }

    public function id(): ?string
    {
        return $this->id;
    }

    /**
     * Set the ID of this object
     *
     * @param string $id
     * @return $this
     */
    public function setID(string $id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     * Add a class to the class list
     *
     * @param string $class
     * @return $this
     */
    public function addClass(string $class)
    {
        $this->classes[] = $class;
        $this->classes = array_unique($this->classes);
        return $this;
    }

    /**
     * Remove a class to the class list
     *
     * @param string $class
     * @return $this
     */
    public function removeClass(string $class)
    {
        $this->classes = array_filter(
            $this->classes,
            function ($e) use ($class) {
                return $e != $class;
            }
        );
        return $this;
    }

    /**
     * Set a data value
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setData(string $key, $value)
    {
        $this->data[$key] = $value;
        return $this;
    }

    /**
     * Unset a data value
     *
     * @param string $key
     * @return $this
     */
    public function unsetData(string $key)
    {
        unset($this->data[$key]);
        return $this;
    }

    /**
     * Set an attribute value
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setAttribute(string $key, $value)
    {
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Unset an attribute value
     *
     * @param string $key
     * @return $this
     */
    public function unsetAttribute(string $key)
    {
        unset($this->attributes[$key]);
        return $this;
    }

    /**
     * Add a child object
     *
     * @param string|Node $child
     * @return $this
     */
    public function addChild($child)
    {
        $this->children[] = $child;
        return $this;
    }

    /**
     * Remove a child object
     *
     * @param string|Node $child
     * @return $this
     */
    public function removeChild($child)
    {
        $this->children = array_filter(
            $this->children,
            function ($e) use ($child) {
                return $e != $child;
            }
        );
        return $this;
    }

    public function classes(): array
    {
        return $this->classes;
    }

    public function children(): array
    {
        return $this->children;
    }

    public function attributes(): array
    {
        $attributes = $this->attributes;
        // set data attributes
        foreach ($this->data as $k => $v) {
            $attributes["data-$k"] = $v;
        }
        // set class attribute
        if ($classes = $this->classes()) {
            $attributes["class"] = implode($classes);
        }
        // set ID attribute
        if ($id = $this->id()) {
            $attributes['id'] = $id;
        }
        return $attributes;
    }

    protected static function encodeValue($value): string
    {
        if ($value === true) {
            return 'true';
        } elseif ($value === false) {
            return 'false';
        } elseif (is_array($value)) {
            return json_encode($value);
        } else {
            return "$value";
        }
    }

    public function toString(): string
    {
        // opening tag
        $html = '<' . $this->tag();
        if ($attributes = $this->attributes()) {
            foreach ($attributes as $name => $value) {
                $html .= ' ' . $name;
                if ($value !== null) {
                    $html .= '="' . htmlentities(static::encodeValue($value)) . '"';
                }
            }
        }
        $html .= '>' . PHP_EOL;
        // children and closing tag if not a void tag
        if (!$this->void()) {
            // children
            foreach ($this->children() as $child) {
                $html .= $child;
            }
            // closing tag
            $html .= '</' . $this->tag . '>' . PHP_EOL;
        }
        return $html;
    }
}
