<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Data;

use Digraph\Helpers\AbstractHelper;

class DataHelper extends AbstractHelper
{
    protected $groups = [];

    /* DDL for table */
    const DDL_FACTS = <<<EOT
CREATE TABLE IF NOT EXISTS digraph_facts (
    fact_id INTEGER PRIMARY KEY,
    fact_namespace TEXT NOT NULL,
    fact_name TEXT NOT NULL,
    fact_value TEXT,
    fact_about TEXT,
    fact_order TEXT,
    fact_data TEXT
);
EOT;

    /* indexes to create on table */
    const IDX = [
        'CREATE INDEX IF NOT EXISTS digraph_facts_namespace_IDX ON digraph_facts (fact_namespace);',
        'CREATE INDEX IF NOT EXISTS digraph_facts_about_IDX ON digraph_facts (fact_about);',
        'CREATE INDEX IF NOT EXISTS digraph_facts_name_IDX ON digraph_facts (fact_name);',
        'CREATE INDEX IF NOT EXISTS digraph_facts_value_IDX ON digraph_facts (fact_value);',
        'CREATE INDEX IF NOT EXISTS digraph_facts_order_IDX ON digraph_facts (fact_order);',
        'CREATE UNIQUE INDEX IF NOT EXISTS digraph_facts_UNIQUE_IDX ON digraph_facts (fact_namespace,fact_about,fact_name);'
    ];

    public function &facts(string $namespace)
    {
        if (!isset($this->groups[$namespace])) {
            $this->groups[$namespace] = new FactGroup($this, $this->pdo, $namespace);
        }
        return $this->groups[$namespace];
    }

    public function construct()
    {
        $this->pdo = $this->cms->pdo();
        //set up JSON function from Destructr
        // $this->pdo->sqliteCreateFunction(
        //     'DH_JSON_EXTRACT',
        //     '\\Destructr\\LegacyDrivers\\SQLiteDriver::JSON_EXTRACT',
        //     2
        // );
        //ensure that tables and indexes exist
        $this->pdo->exec(static::DDL_FACTS);
        foreach (static::IDX as $idx) {
            $this->pdo->exec($idx);
        }
    }
}
