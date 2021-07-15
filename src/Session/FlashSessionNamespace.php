<?php

namespace DigraphCMS\Session;

class FlashSessionNamespace extends SessionNamespace
{
    public function flash(string $key, $value)
    {
        $this->set('next/' . $key, $value);
    }

    public function advance(): void
    {
        // unset all current values
        foreach (array_keys($this->current()) as $key) {
            $this->unset('current/' . $key);
        }
        // copy next to current and delete
        foreach ($this->next() as $key => $value) {
            $this->set('current/' . $key, $value);
            $this->unset('next/' . $key);
        }
    }

    public function current(): array
    {
        $return = [];
        foreach ($this->glob('current/*') as $key => $value) {
            $return[preg_replace('@^current/@', '', $key)] = $value;
        }
        return $return;
    }

    public function next(): array
    {
        $return = [];
        foreach ($this->glob('next/*') as $key => $value) {
            $return[preg_replace('@^next/@', '', $key)] = $value;
        }
        return $return;
    }
}
