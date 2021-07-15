<?php

namespace DigraphCMS\DB;

class PageQuery
{
    protected $result, $fetchAll;
    protected $where;
    protected $where_params;

    public function __construct(string $where, array $params = [])
    {
        $this->where = $where;
        $this->where_params = $params;
    }

    public function execute()
    {
        if (!$this->result) {
            $query = DB::query();
            $query->throwExceptionOnError(true);
            $query = $query->from('pages');
            $query->disableSmartJoin();
            $query->where($this->where, $this->where_params);
            $this->result = $query->execute();
        }
    }

    public function fetch(): ?Page
    {
        if ($result = $this->result->fetch()) {
            return Pages::resultToPage($result);
        } else {
            return null;
        }
    }

    public function fetchAll(): array
    {
        if ($this->fetchAll === null) {
            $this->fetchAll = [];
            while ($page = $this->fetch()) {
                $this->fetchAll[] = $page;
            }
        }
        return $this->fetchAll;
    }
}
