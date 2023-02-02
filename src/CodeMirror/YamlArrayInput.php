<?php

namespace DigraphCMS\CodeMirror;

use Flatrr\FlatArray;

class YamlArrayInput extends CodeMirrorInput
{
    public function __construct()
    {
        parent::__construct();
        $this->mode = 'yaml';
    }

    /**
     * @param string|array|FlatArray $value
     * @return static
     */
    public function setDefault($value)
    {
        if ($value instanceof FlatArray) $value = $value->get();
        if (is_array($value)) $value = static::yamlDump($value);
        return parent::setDefault($value);
    }

    /**
     * @param string|array|FlatArray $value
     * @return static
     */
    public function setValue($value)
    {
        if ($value instanceof FlatArray) $value = $value->get();
        if (is_array($value)) $value = static::yamlDump($value);
        return parent::setValue($value);
    }

    public function default(): array
    {
        $value = parent::default();
        if (is_array($value)) return $value;
        elseif ($value) return spyc_load($value);
        else return [];
    }

    public function value($useDefault = false): array
    {
        $value = parent::value($useDefault);
        if (is_array($value)) return $value;
        elseif ($value) return spyc_load($value);
        else return [];
    }

    protected static function yamlDump(array $value): string
    {
        $value = spyc_dump($value);
        $value = preg_replace_callback('/^(  )+/m', function ($m) {
            return str_repeat("\t", strlen($m[0]) / 2);
        }, $value);
        return $value;
    }

    public function children(): array
    {
        return [
            htmlentities(static::yamlDump($this->value(true)))
        ];
    }
}
