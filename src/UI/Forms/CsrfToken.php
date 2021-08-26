<?php

namespace DigraphCMS\UI\Forms;

use DigraphCMS\Session\Cookies;
use Formward\SystemFields\AbstractSystemField;
use Formward\SystemFields\TokenInterface;

class CsrfToken extends AbstractSystemField implements TokenInterface
{
    public function containerMayWrap(): bool
    {
        return false;
    }

    protected function htmlAttributes()
    {
        $attr = parent::htmlAttributes();
        $attr['value'] = $this->value();
        return $attr;
    }

    /**
     * Check a value against my token
     */
    public function test(?string $token = null): bool
    {
        $token = $token ?? $this->submittedValue();
        return $token == $this->value();
    }

    protected function tokenName(): string
    {
        return 'form_' . md5($this->name());
    }

    /**
     * Clear my token
     */
    public function clear(): void
    {
        Cookies::unset('csrf', $this->tokenName(), true);
    }

    /**
     * Always return a CSRF token. This field disregards default/submittedValue
     */
    public function value($value = null)
    {
        return
            Cookies::get('csrf', $this->tokenName()) ??
            Cookies::set('csrf', $this->tokenName(), bin2hex(random_bytes(16)), false, true);
    }
}
