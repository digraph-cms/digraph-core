<?php

namespace DigraphCMS\HTML;

use DigraphCMS\URL\URL;

class A extends Tag
{
    protected $tag = 'a';
    protected $href = null;
    protected $target = null;
    protected $frameTarget = null;

    /**
     * @param string|URL|null $href
     * @param string|null $target
     * @param string|null $frameTarget
     */
    public function __construct($href = null, string $target = null, string $frameTarget = null)
    {
        $this->setHref($href)
            ->setTarget($target)
            ->setFrameTarget($frameTarget);
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        if ($this->href()) $attributes['href'] = @$attributes['href'] ?? $this->href();
        if ($this->target()) $attributes['target'] = @$attributes['target'] ?? $this->target();
        if ($this->frameTarget()) $attributes['data-target'] = @$attributes['data-target'] ?? $this->frameTarget();
        return $attributes;
    }

    public function children(): array
    {
        $children = parent::children();
        if (!$children && $href = $this->href()) {
            if ($href instanceof URL) {
                return [$href->name()];
            }
        }
        return $children;
    }

    /**
     * The url this should link to
     *
     * @return string|URL|null
     */
    public function href()
    {
        return $this->href;
    }

    public function target(): ?string
    {
        return $this->target;
    }

    public function frameTarget(): ?string
    {
        return $this->frameTarget;
    }

    /**
     * Set the href of this link
     *
     * @param string|URL|null $href
     * @return static
     */
    public function setHref($href=null) {
        $this->href = $href;
        return $this;
    }

    /**
     * Set the target of this link
     *
     * @param string|null $target
     * @return static
     */
    public function setTarget($target=null) {
        $this->target = $target;
        return $this;
    }

    /**
     * Set the navigation frame target of this link
     *
     * @param string|null $target
     * @return static
     */
    public function setFrameTarget($target=null) {
        $this->frameTarget = $target;
        return $this;
    }
}
