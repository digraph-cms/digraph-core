<?php

namespace DigraphCMS\Datastore;

use DigraphCMS\DB\AbstractMappedSelect;
use DigraphCMS\DB\DB;

/**
 * @method DatastoreItem|null fetch()
 * @method DatastoreItem[] fetchAll()
 */
class DatastoreSelect extends AbstractMappedSelect
{
    protected $returnObjectClass = DatastoreItem::class;

    public function __construct()
    {
        return parent::__construct(DB::query()->from('datastore'));
    }
}
