<?php
/* Digraph Core | https://github.com/digraph-cms/digraph-core | MIT License */

namespace Digraph\Helpers;

use Digraph\Urls\Url;

class StaticHelper extends \Digraph\Helpers\AbstractHelper
{
    protected $pdo;

    const CHARS = '$-_.+!*(),';

    /* DDL for table */
    const DDL = <<<EOT
CREATE TABLE IF NOT EXISTS digraph_static_pages (
	static_url TEXT NOT NULL
);
EOT;

    /* indexes to create on table */
    const IDX = [
        'CREATE UNIQUE INDEX IF NOT EXISTS digraph_static_pages_url_IDX ON digraph_static_pages (static_url);'
    ];

    public function hook_cron(): array
    {
        $updated = [];
        $list = [];
        $i = 0;
        foreach ($this->list() as $url) {
            $url = $this->cms->helper('urls')->parse($url);
            $path = $this->path($url);
            if ($path) {
                $time = file_exists($path) ? filemtime($path) : $i++;
                $list[$time] = $url;
            }
        }
        ksort($list);
        $startTime = time();
        while ($list && (time() - $startTime) < 15) {
            $todo = array_shift($list);
            $this->create($todo);
        }
        return [
            'result' => count($updated),
            'names' => $updated
        ];
    }

    public function construct()
    {
        $this->pdo = $this->cms->pdo();
        //ensure that table and indexes exist
        $this->pdo->exec(static::DDL);
        foreach (static::IDX as $idx) {
            $this->pdo->exec($idx);
        }
    }

    public function create(Url $url): bool
    {
        // can't staticify control panel pages
        if (strpos($url->pathString(), '_controlpanel/') === 0) {
            return false;
        }
        // can't staticify user pages
        if (strpos($url->pathString(), '_user/') === 0) {
            return false;
        }
        // can't staticify log pages
        if (strpos($url->pathString(), '_logging/') === 0) {
            return false;
        }
        // can't staticify fastphp pages
        if (strpos($url->pathString(), '_fastphp/') === 0) {
            return false;
        }
        // can't staticify json pages
        if (strpos($url->pathString(), '_json/') === 0) {
            return false;
        }
        // can't staticify cron pages
        if (strpos($url->pathString(), '_cron/') === 0) {
            return false;
        }
        // get path
        $path = $this->path($url);
        if (!$path) {
            return false;
        }
        // if this url is already static, clear it before continuing
        @unlink($this->path($url));
        // make request and verify that it succeeds
        // we do this over HTTP just to ensure we get the guest version with
        // no weird side effects
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url->__toString());
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
        $content = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code != 200) {
            return false;
        }
        // we have a good response
        $content = $this->modifyOutput($content, true);
        // save content into file and record in database
        $s = $this->pdo->prepare(
            'INSERT INTO digraph_static_pages (static_url) VALUES (:url)'
        );
        $s->execute([':url' => $url->pathString()]);
        $this->cms->helper('filesystem')->mkdir_for($path);
        $this->cms->helper('filesystem')->put($content, $path, false);
        return true;
    }

    public function exists(Url $url): bool
    {
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_static_pages WHERE :url'
        );
        $s->execute([
            ':url' => $url->pathString()
        ]);
        return !!$s->fetch();
    }

    public function delete(Url $url)
    {
        unlink($this->path($url));
        $s = $this->pdo->prepare(
            'DELETE FROM digraph_static_pages WHERE static_url = :url'
        );
        $s->execute([
            ':url' => $url->pathString()
        ]);
    }

    public function list(): array
    {
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_static_pages'
        );
        $s->execute();
        return array_map(
            function ($row) {
                return $row[0];
            },
            $s->fetchAll()
        );
    }

    /**
     * Modify output
     *
     * @param string $content
     * @return string
     */
    protected function modifyOutput(string $content): string
    {
        return $content;
    }

    public function path(Url $url): ?string
    {
        // can't staticize URLs with args
        if ($url['args']) {
            return null;
        }
        // get web path
        $path = $this->cms->config['paths.web'];
        // build path from url path
        $path .= '/' . $url->pathString();
        if (substr($path, -1) != '/') {
            $path .= '/';
        }
        $path .= 'index.html';
        return $path;
    }
}
