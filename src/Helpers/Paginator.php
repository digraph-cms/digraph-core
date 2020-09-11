<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Destructr\Search;
use Digraph\Mungers\Package;

class Paginator extends AbstractHelper
{
    protected $package;
    protected $arg;
    protected $pages;
    protected $page;

    public function pagelink($page, $string = null)
    {
        if ($page < 1 || $page > $this->pages || !$this->package) {
            return '';
        }
        $url = $this->package->url();
        $url['args.' . $this->arg] = $page;
        $link = $url->html();
        $link->content = $string ? $string : $page;
        if ($page == $this->page) {
            $link->addClass('current-page');
        }
        return "$link";
    }

    public function paginateSearch(Search $search, Package $package, callable $rowCallback, array $headers = [], int $perpage = 20, string $arg = 'p')
    {
        return $this->paginate(
            $search->count(),
            $package,
            $arg,
            $perpage,
            function ($start, $end) use ($search, $rowCallback, $headers, $perpage) {
                $search = clone $search;
                $search->offset($start - 1);
                $search->limit($perpage);
                $out = '<table>';
                if ($headers) {
                    $out .= "<tr><th>" . implode('</th><th>', $headers) . "</th></tr>";
                }
                foreach ($search->execute() as $i) {
                    if ($row = $rowCallback($i)) {
                        $out .= "<tr><td>" . implode("</td><td>", $row) . "</td></tr>";
                    }
                }
                $out .= '</table>';
                return $out;
            }
        );
    }

    public function paginate($items, $package, $arg, $perpage, $callback, $fields = [])
    {
        if (is_array($items)) {
            $count = count($items);
        } else {
            $count = $items;
        }
        //verify that URL is sane
        $page = $package['url.args.' . $arg] ? intval($package['url.args.' . $arg]) : 1;
        $pages = ceil($count / $perpage);
        if ($pages == 0) {
            $pages = 1;
        }
        if ($page < 1 || $page > $pages) {
            $package->error(404, 'invalid page number');
        }
        $start = $perpage * ($page - 1) + 1;
        $end = $start + $perpage - 1;
        if ($end > $count) {
            $end = $count;
        }
        //build content
        if (is_array($items)) {
            $results = '';
            foreach (array_slice($items, $start - 1, $perpage) as $e) {
                $results .= PHP_EOL . $callback($e);
            }
        } else {
            $results = $callback($start, $end);
        }
        //render with template
        $this->package = $package;
        $this->arg = $arg;
        $this->pages = $pages;
        $this->page = $page;
        $fields['page'] = $page;
        $fields['pages'] = $pages;
        $fields['paginator'] = $this;
        $fields['start'] = $start;
        $fields['end'] = $end;
        $fields['count'] = $count;
        $fields['results'] = $results;
        $out = $this->cms->helper('templates')->render(
            'digraph/paginated.twig',
            $fields
        );
        return $out;
    }
}
