<?php

namespace DigraphCMS\Datastore;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Session\Session;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;
use Flatrr\FlatArray;

class DatastoreItem
{
    protected $id, $ns, $grp, $key, $value, $data;
    protected $created, $created_by, $updated, $updated_by;
    protected $data_fltr;

    public function update(): bool
    {
        return !!DB::query()->update(
            'datastore',
            [
                '`value`' => $this->value(),
                '`data`' => json_encode($this->data()->get()),
                '`updated`' => time(),
                '`updated_by`' => Session::uuid(),
            ],
            $this->id()
        )->execute();
    }

    public function delete(): bool
    {
        return !!DB::query()->delete(
            'datastore',
            $this->id()
        )->execute();
    }

    public function key(): string
    {
        return $this->key;
    }

    /**
     * @param string|null $value
     * @return static
     */
    public function setValue(?string $value)
    {
        if ($value === '') $value = null;
        $this->value = $value;
        return $this;
    }

    /**
     * @param array|FlatArray|null $data
     * @return static
     */
    public function setData($data)
    {
        if ($data instanceof FlatArray) $this->data_fltr = $data;
        if (is_array($data)) $this->data_fltr = new FlatArray($data);
        else $this->data_fltr = new FlatArray();
        return $this;
    }

    public function value(): ?string
    {
        if ($this->value === '') $this->value = null;
        return $this->value;
    }

    public function data(): FlatArray
    {
        return $this->data_fltr
            ?? $this->data_fltr = new FlatArray(json_decode($this->data, true));
    }

    public function group(): DatastoreGroup
    {
        return new DatastoreGroup($this->ns, $this->grp);
    }

    public function groupName(): string
    {
        return $this->grp;
    }

    public function namespace(): DatastoreNamespace
    {
        return new DatastoreNamespace($this->ns);
    }

    public function namespaceName(): string
    {
        return $this->ns;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function createdBy(): User
    {
        return Users::user($this->created_by);
    }

    public function updatedBy(): User
    {
        return Users::user($this->updated_by);
    }

    public function createdByUUID(): string
    {
        return $this->created_by;
    }

    public function updatedByUUID(): string
    {
        return $this->updated_by;
    }

    public function created(): DateTime
    {
        return (new DateTime)->setTimestamp($this->created);
    }

    public function updated(): DateTime
    {
        return (new DateTime)->setTimestamp($this->updated);
    }
}
