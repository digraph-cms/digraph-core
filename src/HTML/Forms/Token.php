<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\Session\Cookies;

class Token extends INPUT
{
    protected $CSRF = true;
    protected $uniqueCSRF = false;

    public function __construct(FormWrapper $form)
    {
        $this->setForm($form);
        $this->setID('token');
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
     * @return $this
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
     * @return $this
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

    public function default(): string {
        return $this->token();
    }

    public function attributes(): array
    {
        $attributes = parent::attributes();
        $attributes['type'] = 'hidden';
        $attributes['value'] = $this->token();
        return $attributes;
    }
}
