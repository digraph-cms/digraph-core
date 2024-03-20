<?php

namespace DigraphCMS\CodeMirror;

use DigraphCMS\Exception;
use DigraphCMS\ExceptionLog;
use Flatrr\FlatArray;
use Symfony\Component\Yaml\Yaml;

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
        elseif ($value) return Yaml::parse($value);
        else return [];
    }

    /**
     * @param bool $useDefault 
     * @return array<mixed,mixed>|null
     */
    public function value(bool $useDefault = false): mixed
    {
        /** @var string|null|array<mixed,mixed> might be an array, because of default */
        $value = parent::value($useDefault);
        if (is_array($value)) return $value;
        elseif ($value) return Yaml::parse($value);
        else return [];
    }

    protected static function yamlDump(array $value): string
    {
        $input = $value;
        try {
            $value = Yaml::dump($value, 2, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
            $value = preg_replace_callback('/^(  )+/m', function ($m) {
                return str_repeat("\t", strlen($m[0]) / 2);
            }, $value);
            return $value;
        } catch (\Throwable $th) {
            ExceptionLog::log(new Exception('Failed to parse YAML for input field', $input, $th));
            return '';
        }
    }

    public function children(): array
    {
        return [
            htmlentities(static::yamlDump($this->value(true)))
        ];
    }
}
