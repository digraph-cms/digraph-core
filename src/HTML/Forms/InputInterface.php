<?php

namespace DigraphCMS\HTML\Forms;

interface InputInterface
{
    /**
     * @param FORM $form
     * @return $this
     */
    public function setForm(FORM $form);
    public function form(): ?FORM;
    public function submitted(): bool;
    public function id(): ?string;

    public function validationError(): ?string;
    public function addValidator(callable $validator);

    public function required(): bool;
    public function default();
    public function value($useDefault = false);

    /**
     * @param bool $required
     * @param string|null $message
     * @return $this
     */
    public function setRequired(bool $required, string $message = null);

    /**
     * @param mixed $required
     * @return $this
     */
    public function setDefault($default);

    /**
     * @param mixed $required
     * @return $this
     */
    public function setValue($value);
}
