<?php

namespace DigraphCMS\HTML\Forms;

use DigraphCMS\HTML\TagInterface;

interface InputInterface extends TagInterface
{
    /**
     * @param FormWrapper $form
     * @return static
     */
    public function setForm(FormWrapper $form);
    public function form(): ?FormWrapper;
    public function submitted(): bool;
    public function id(): ?string;

    public function validationError(): ?string;
    public function addValidator(callable $validator);

    public function required(): bool;
    public function default();
    public function value(bool $useDefault = false): mixed;

    /**
     * @param bool $required
     * @param string|null $message
     * @return static
     */
    public function setRequired(bool $required, string $message = null);

    /**
     * @param mixed $default
     * @return static
     */
    public function setDefault($default);

    /**
     * @param mixed $value
     * @return static
     */
    public function setValue($value);
}
