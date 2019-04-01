<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

class Paginator extends AbstractHelper
{
    public function paginate($items, $package, $arg, $perpage, $callback)
    {
        //verify that URL is sane
        $count = count($items);
        $page = $package['url.args.'.$arg]?intval($package['url.args.'.$arg]):1;
        $pages = ceil($count/$perpage);
        if ($page < 1 || $page > $pages) {
            $package->error(404, 'invalid page number');
        }
        $start = $perpage*($page-1)+1;
        $end = $start + $perpage-1;
        //build content
        $results = [];
        foreach (array_slice($items, $start-1, $perpage) as $e) {
            $results[] = $callback($e);
        }
        //build list of page links
        $pagelinks = [];
        for ($i=1; $i <= $pages; $i++) {
            $url = $package->url();
            $url['args.page'] = $i;
            $link = $url->html();
            if ($i == $page) {
                $link->addClass('current');
            }
            $link->content = $i;
            $pagelinks[$i] = $link;
        }
        //render with template
        return $this->cms->helper('templates')->render(
            'digraph/paginated.twig',
            [
                'page' => $page,
                'pages' => $pages,
                'pagelinks' => $pagelinks,
                'start' => $start,
                'end' => $end,
                'count' => $count,
                'results' => $results
            ]
        );
    }
}
