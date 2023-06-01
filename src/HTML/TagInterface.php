<?php

namespace DigraphCMS\HTML;

interface TagInterface {
    public function style(): array;

    /**
     * Set an inline CSS attribute
     *
     * @param string $key
     * @param string|null $value
     * @return static
     */
    public function setStyle(string $key, ?string $value);

    public function tag(): string;

    public function void(): bool;

    public function id(): ?string;

    /**
     * Set the ID of this object
     *
     * @param string $id
     * @return static
     */
    public function setID(string $id);

    /**
     * Add a class to the class list
     *
     * @param string $class
     * @return static
     */
    public function addClass(string $class);

    /**
     * add any number of classes from a string
     *
     * @param string $classes
     * @return static
     */
    public function addClassString(string $classes);

    /**
     * Remove a class to the class list
     *
     * @param string $class
     * @return static
     */
    public function removeClass(string $class);

    /**
     * Set a data value
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function setData(string $key, $value);

    /**
     * Unset a data value
     *
     * @param string $key
     * @return static
     */
    public function unsetData(string $key);

    /**
     * Set an attribute value
     *
     * @param string $key
     * @param mixed $value
     * @return static
     */
    public function setAttribute(string $key, $value = null);

    /**
     * Unset an attribute value
     *
     * @param string $key
     * @return static
     */
    public function unsetAttribute(string $key);

    /**
     * Add a child object
     *
     * @param string|Node $child
     * @return static
     */
    public function addChild($child);

    /**
     * Remove a child object
     *
     * @param string|Node $child
     * @return static
     */
    public function removeChild($child);

    public function classes(): array;

    public function children(): array;

    public function attributes(): array;

    public function toString(): string;
}