<?php

namespace DigraphCMS\RichMedia\Types;

use ArrayAccess;
use DateTime;
use DigraphCMS\Content\Filestore;
use DigraphCMS\Content\FilestoreFile;
use DigraphCMS\Content\Page;
use DigraphCMS\Content\Pages;
use DigraphCMS\Digraph;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Text;
use DigraphCMS\RichMedia\RichMedia;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\UI\Toolbars\ToolbarSeparator;
use DigraphCMS\UI\Toolbars\ToolbarSpacer;
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

    protected $uuid, $pageUUID, $name;
    protected $created, $created_by;
    protected $updated, $updated_by;

    abstract public static function class(): string;
    abstract public static function className(): string;
    abstract public static function description(): string;
    abstract public static function shortCode(ShortcodeInterface $code, $media): ?string;

    public function __construct(array $data = [], array $metadata = [])
    {
        $this->uuid = @$metadata['uuid'] ?? Digraph::uuid();
        $this->pageUUID = @$metadata['page_uuid'];
        $this->created = @$metadata['created'] ?? new DateTime();
        $this->created_by = @$metadata['created_by'];
        $this->updated = @$metadata['updated'] ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = @$metadata['updated_by'];
        $this->name = @$metadata['name'] ?? 'Unnamed media';
        $this->rawSet(null, $data);
        $this->changed = false;
    }

    public function file(): FilestoreFile
    {
        return Filestore::get($this['file']);
    }

    public function insertInterface(): DIV
    {
        $id = Digraph::uuid();
        $toolbar = (new DIV())
            ->addClass('toolbar');
        $toolbar->addChild(
            (new ToolbarLink('insert embed code', 'post-add', null, null))
                ->setAttribute('onclick', sprintf(
                    'this.dispatchEvent(Digraph.RichContent.insertTagEvent("%s", %s))',
                    $this->insertTagName(),
                    Format::js_encode_object($this->insertTagOptions())
                ))
        );
        // TODO: customizeable embedding links
        $toolbar->addChild(new ToolbarSpacer);
        $toolbar->addChild(new ToolbarSeparator);
        $toolbar->addChild(new Text(sprintf('<pre id="%s">%s</pre>', $id, $this->defaultTag())));
        $toolbar->addChild(
            (new ToolbarLink('copy embed code', 'copy', null, null))
                ->setAttribute('onclick', sprintf(
                    'navigator.clipboard.writeText(document.getElementById("%s").innerHTML)',
                    $id
                ))
        );
        return $toolbar;
    }

    public function insertTagOptions(): array
    {
        return [
            '_' => $this->uuid()
        ];
    }

    public function insertTagName(): string
    {
        return $this->tagName();
    }

    public function defaultTag(): string
    {
        return sprintf(
            '[%s="%s"/]',
            $this->tagName(),
            $this->uuid()
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

    public function media(): ?Page
    {
        return Pages::get($this->pageUUID);
    }

    public function pageUUID(): ?string
    {
        return $this->pageUUID;
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

    public function updatedLast(): DateTime
    {
        return clone $this->updated_last;
    }
}
