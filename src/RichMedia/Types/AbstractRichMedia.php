<?php

namespace DigraphCMS\RichMedia\Types;

use ArrayAccess;
use DateTime;
use DigraphCMS\Config;
use DigraphCMS\Content\AbstractPage;
use DigraphCMS\Content\Pages;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\Session\Session;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Flatrr\FlatArrayTrait;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

abstract class AbstractRichMedia implements ArrayAccess
{
    use FlatArrayTrait {
        set as protected rawSet;
        unset as protected rawUnset;
    }

    protected $uuid, $parent, $name;
    protected $created, $created_by;
    protected $updated, $updated_by;

    abstract public static function className(): string;
    abstract public static function description(): string;
    abstract public function shortCode(ShortcodeInterface $code): ?string;
    abstract protected function prepareForm(FormWrapper $form, $create = false);

    public static function class(): string
    {
        static $classes = [];
        $class = get_called_class();
        return @$classes[$class] ?? $classes[$class] = static::getClass($class);
    }

    protected static function getClass(string $thisClass): string
    {
        $thisClass = preg_replace('/^[^\\\]/', '\\\$0', $thisClass);
        foreach (Config::get('rich_media_types') as $name => $class) {
            if ($class == $thisClass) return $name;
        }
        throw new \Exception("Rich Media class $thisClass is not configured");
    }

    public function editForm($create = false): FormWrapper
    {
        $form = new FormWrapper($this->uuid());
        $this->prepareForm($form, $create);
        if ($create) {
            $form->addCallback(function () {
                $this->insert();
            });
        } else {
            $form->addCallback(function () {
                $this->update();
            });
        }
        return $form;
    }

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->parent = @$metadata['parent'];
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'] ?? Session::uuid();
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_by = @$metadata['updated_by'] ?? Session::uuid();
        $this->name = @$metadata['name'] ?? '';
        $this->rawSet(null, $data);
    }

    public function tagOptions(): array
    {
        return [
            '_' => $this->uuid()
        ];
    }

    public function tagOptionsString(): string
    {
        $output = [];
        $options = $this->tagOptions();
        if ($first = @$options['_']) {
            unset($options['_']);
            $output[] = '="' . $first . '"';
        }
        foreach ($options as $k => $v) {
            $output[] = $k . '="' . $v . '"';
        }
        return implode(' ', $output);
    }

    public function defaultTag(): string
    {
        return sprintf(
            '[%s%s/]',
            $this->tagName(),
            $this->tagOptionsString(),
        );
    }

    public function defaultWrappingTag(): ?string
    {
        return sprintf(
            '[%s%s]{content}[/%s]',
            $this->tagName(),
            $this->tagOptionsString(),
            $this->tagName(),
        );
    }

    public function tagName(): string
    {
        return $this->class();
    }

    public function name(string $set = null): string
    {
        if ($set) {
            $this->name = $set;
        }
        return $this->name;
    }

    public function media(): ?AbstractPage
    {
        return Pages::get($this->parent);
    }

    public function parent(): ?string
    {
        return $this->parent;
    }

    public function setParent(string $pageUUID)
    {
        $this->parent = $pageUUID;
        return $this;
    }

    public function insert()
    {
        return RichMedia::insert($this);
    }

    public function update()
    {
        return RichMedia::update($this);
    }

    public function delete()
    {
        return RichMedia::delete($this);
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function setUUID(string $uuid)
    {
        $this->uuid = $uuid;
        return $this;
    }

    public function createdBy(): User
    {
        return $this->created_by ? Users::user($this->created_by) : Users::guest();
    }

    public function updatedBy(): User
    {
        return $this->updated_by ? Users::user($this->updated_by) : Users::guest();
    }

    public function createdByUUID(): ?string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): ?string
    {
        return $this->updated_by;
    }

    public function created(): DateTime
    {
        return clone $this->created;
    }

    public function updated(): DateTime
    {
        return clone $this->updated;
    }
}
