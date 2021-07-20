<?php

namespace DigraphCMS\DB;

use ArrayAccess;
use DateTime;
use DigraphCMS\Digraph;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\Session\Session;
use Flatrr\FlatArrayTrait;

abstract class AbstractDataObject implements ArrayAccess
{
    const SOURCE = null;

    use FlatArrayTrait {
        set as protected rawSet;
        unset as protected rawUnset;
    }

    protected $changed = false;

    public function __construct(array $data = [], string $uuid = null, DateTime $created = null, string $created_by = null, DateTime $updated = null, string $updated_by = null)
    {
        $this->uuid = $uuid ?? Digraph::uuid();
        $this->created = $created ?? new DateTime();
        $this->created_by = $created_by ?? Session::user();
        $this->updated = $updated ?? new DateTime();
        $this->updated_last = clone $this->updated;
        $this->updated_by = $updated_by ?? Session::user();
        $this->rawSet(null,$data);
        Dispatcher::dispatchEvent('onPageConstruct', [$this]);
        $this->changed = false;
    }

    public function set(?string $name, $value)
    {
        if (!(static::SOURCE)::COLNAMES['data']) {
            throw new \Exception("This DataObject class doesn't support arbitrary JSON data");
        }
        $name = strtolower($name);
        if ($this->get($name) === $value) {
            return;
        }
        $this->touch();
        $this->rawSet($name, $value);
    }

    function unset(?string $name)
    {
        if (isset($this[$name])) {
            $this->touch();
            $this->rawUnset($name);
        }
    }

    public function insert()
    {
        (static::SOURCE)::insert($this);
    }

    public function update()
    {
        if ($this->changed()) {
            (static::SOURCE)::update($this);
        }
    }

    public function delete()
    {
        (static::SOURCE)::delete($this);
    }

    public function touch()
    {
        $this->changed = true;
        $this->updated_by = Session::user();
    }

    public function changed(): bool
    {
        return $this->changed;
    }

    public function uuid(): string
    {
        return $this->uuid;
    }

    public function createdBy(): string
    {
        return $this->created_by;
    }

    public function updatedBy(): string
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
        return clone $this->updated;
    }
}