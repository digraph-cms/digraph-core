<?php

namespace DigraphCMS\Security;

use DigraphCMS\HTML\DIV;

class SecureContent extends DIV
{
    protected static $idCounter = 0;

    public function __construct(string $id = null)
    {
        $this->setID($id ?? 'secure-content-' . static::$idCounter++);
    }

    public function classes(): array
    {
        $classes = parent::classes();
        $classes[] = 'secure-content';
        $classes[] = 'navigation-frame';
        $classes[] = 'navigation-frame--stateless';
        return array_unique($classes);
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        if (Security::flagged()) {
            $attributes['data-initial-source'] = Security::captchaUrl($this->id());
            $attributes['data-target'] = '_frame';
        }
        return $attributes;
    }

    public function children(): array
    {
        $children = parent::children();
        if (!Security::flagged()) $children[] = '<!--SECURE_CONTENT_LOADED-->';
        return $children;
    }

    public function __toString(): string
    {
        if (Security::flagged()) {
            // override to display a div requiring CAPTCHA
            // opening tag
            $html = '<' . $this->tag();
            if ($attributes = $this->attributes()) {
                foreach ($attributes as $name => $value) {
                    $html .= ' ' . $name;
                    if ($value !== null) {
                        $html .= '="' . static::escapeValue(static::encodeValue($value)) . '"';
                    }
                }
            }
            $html .= '>';
            // uses data-initial-source to display CAPTCHA, but also includes a noscript link fallback
            $html .= '<noscript><a href="' . Security::captchaUrl() . '">CAPTCHA required to continue</a></noscript>';
            // closing tag
            $html .= '</' . $this->tag . '>';
            return $html;
        } else {
            // display using parent method
            return parent::__toString();
        }
    }
}
