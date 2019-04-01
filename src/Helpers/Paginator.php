<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

class Paginator extends AbstractHelper
{
    protected $package;
    protected $arg;
    protected $pages;
    protected $page;

    public function pagelink($page, $string=null)
    {
        if ($page < 1 || $page > $this->pages || !$this->package) {
            return '';
        }
        $url = $this->package->url();
        $url['args.'.$this->arg] = $page;
        $link = $url->html();
        $link->content = $string?$string:$page;
        if ($page == $this->page) {
            $link->addClass('current-page');
        }
        return "$link";
    }

    public function paginate($items, $package, $arg, $perpage, $callback)
    {
        //verify that URL is sane
        $count = count($items);
        $page = $package['url.args.'.$arg]?intval($package['url.args.'.$arg]):1;
        $pages = ceil($count/$perpage);
        if ($pages == 0) {
            $pages = 1;
        }
        if ($page < 1 || $page > $pages) {
            $package->error(404, 'invalid page number');
        }
        $start = $perpage*($page-1)+1;
        $end = $start + $perpage-1;
        if ($end > $count) {
            $end = $count;
        }
        //build content
        $results = [];
        foreach (array_slice($items, $start-1, $perpage) as $e) {
            $results[] = $callback($e);
        }
        //render with template
        $this->package = $package;
        $this->arg = $arg;
        $this->pages = $pages;
        $this->page = $page;
        return $this->cms->helper('templates')->render(
            'digraph/paginated.twig',
            [
                'page' => $page,
                'pages' => $pages,
                'paginator' => $this,
                'start' => $start,
                'end' => $end,
                'count' => $count,
                'results' => $results
            ]
        );
    }
}
