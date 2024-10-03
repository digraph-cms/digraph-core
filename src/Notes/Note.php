<?php

namespace DigraphCMS\Notes;

use DateTime;
use DigraphCMS\Datastore\Datastore;
use DigraphCMS\SafeContent\SafeBBCode;
use DigraphCMS\UI\Format;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Flatrr\FlatArray;

class Note
{
    protected readonly int $id; // @phpstan-ignore-line
    protected readonly string $ns; // @phpstan-ignore-line
    protected readonly string $grp; // @phpstan-ignore-line
    protected readonly string $key; // @phpstan-ignore-line
    protected readonly string $value; // @phpstan-ignore-line
    protected readonly string $data; // @phpstan-ignore-line
    protected readonly int $created; // @phpstan-ignore-line
    protected readonly string $created_by; // @phpstan-ignore-line
    protected readonly int $updated; // @phpstan-ignore-line
    protected readonly string $updated_by; // @phpstan-ignore-line
    protected FlatArray $data_array;

    public function update(
        string $value,
        string $text,
    ): void {
        $existing = Datastore::getByID($this->id);
        $existing->setValue($value);
        $existing->setData([
            'text' => $text,
        ]);
        $existing->update();
    }

    public function datastoreId(): int
    {
        return $this->id;
    }

    public function url_admin(): URL
    {
        return new URL('/admin/datastore/item:' . $this->id);
    }

    public function title(bool $raw = false): string
    {
        if ($raw) return $this->value;
        else return htmlentities($this->value);
    }

    public function text(): string
    {
        return $this->data()['text'];
    }

    public function html(): string
    {
        return SafeBBCode::parse($this->text());
    }

    public function data()
    {
        return $this->data_array
            ?? $this->data_array = new FlatArray(json_decode($this->data, true));
    }

    public function createdBy(): User
    {
        return Users::get($this->created_by);
    }

    public function created(): DateTime
    {
        return DateTime::createFromFormat(
            'U',
            (string)$this->created,
            Format::timezone()
        );
    }

    public function updatedBy(): User
    {
        return Users::get($this->updated_by);
    }

    public function updated(): DateTime
    {
        return DateTime::createFromFormat(
            'U',
            (string)$this->updated,
            Format::timezone()
        );
    }

    public function namespace(): NotesNamespace
    {
        return new NotesNamespace(substr($this->ns, 6));
    }

    public function group(): NotesGroup
    {
        return $this->namespace()->group($this->grp);
    }

    public function uuid(): string
    {
        return $this->key;
    }
}
