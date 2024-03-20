<?php

namespace DigraphCMS\CodeMirror;

use DigraphCMS\Exception;
use DigraphCMS\ExceptionLog;
use DigraphCMS\UI\Notifications;
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
        return static::yamlParse(parent::default());
    }

    /**
     * @param bool $useDefault 
     * @return array<mixed,mixed>|null
     */
    public function value(bool $useDefault = false): mixed
    {
        return static::yamlParse(parent::value($useDefault));
    }

    /**
     * 
     * @param string|null|array<mixed,mixed> $value 
     * @return array 
     */
    protected static function yamlParse($value): array
    {
        $input = $value;
        if (is_array($value)) return $value;
        elseif ($value) {
            try {
                $value = preg_replace_callback('/^(\t)+/m', function ($m) {
                    return str_repeat("  ", strlen($m[0]) / 2);
                }, $value);
                return Yaml::parse($value);
            } catch (\Throwable $th) {
                Notifications::error("A YAML input field failed to parse. Submitting the form it is in may cause data loss.");
                ExceptionLog::log(new Exception("Failed to parse YAML", $input, $th));
                return [];
            }
        } else return [];
    }

    protected static function yamlDump(array $value): string
    {
        $value = Yaml::dump($value, 2, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);
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
