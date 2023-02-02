<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Session\Cookies;

class Token extends INPUT
{
    protected $CSRF = true;
    protected $uniqueCSRF = false;
    protected $doNotUse = false;

    public function __construct(FormWrapper $form)
    {
        $this->setForm($form);
        $this->setID('token');
    }

    public function doNotUse(): bool
    {
        return $this->doNotUse;
    }

    /**
     * Set a flag that allows this token to be not used, which disables automatic
     * form submission, but simplifies things when you're using GET requests
     * and whether the form was "submitted" doesn't matter, like for search
     * forms.
     *
     * @param boolean $doNotUse
     * @return static
     */
    public function setDoNotUse(bool $doNotUse)
    {
        $this->doNotUse = $doNotUse;
        return $this;
    }

    public function validationError(): ?string
    {
        return null;
    }

    public function CSRF(): bool
    {
        return $this->CSRF;
    }

    public function uniqueCSRF(): bool
    {
        return $this->uniqueCSRF;
    }

    public function submitted(): bool
    {
        return $this->token() == $this->submittedValue();
    }

    /**
     * Set whether to use proper CSRF tokens, if set to false a simple submitted
     * value will be generated from some basic user fingerprint information.
     *
     * @param boolean $useCSRF
     * @return static
     */
    public function setCSRF(bool $useCSRF)
    {
        $this->CSRF = $useCSRF;
        return $this;
    }

    /**
     * Set whether to use unique one-time CSRF tokens that are discarded after
     * validation. Use this to create forms that cannot be double-submitted.
     *
     * @param boolean $oneTimeTokens
     * @return static
     */
    public function setUniqueCSRF(bool $oneTimeTokens)
    {
        $this->uniqueCSRF = $oneTimeTokens;
        return $this;
    }

    public function token(): string
    {
        if ($this->CSRF()) {
            if ($this->uniqueCSRF()) {
                return Cookies::csrfToken(
                    'form_' . crc32(serialize([
                        $this->form()->id(),
                        $this->form()->action(),
                        $this->form()->method()
                    ]))
                );
            } else {
                return Cookies::csrfToken('forms');
            }
        } else {
            return '1';
        }
    }

    public function default(): string
    {
        return $this->token();
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes['type'] = 'hidden';
        $attributes['value'] = $this->token();
        return $attributes;
    }

    public function toString(): string
    {
        if ($this->doNotUse) return '';
        else return parent::toString();
    }
}
