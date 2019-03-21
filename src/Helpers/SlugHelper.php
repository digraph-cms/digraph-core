<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\CMS;

/**
 * SlugHelper manages the non-canonical URLs that nouns can be found at. The
 * default behavior of this helper is to keep all past slugs of any given noun
 * indefinitely. That way all past URLs of a given noun will keep working, even
 * if you change them later.
 */
class SlugHelper extends \Digraph\Helpers\AbstractHelper
{
    const CHARS = '$-_.+!*\'(),';

    /* DDL for table */
    const DDL = <<<EOT
CREATE TABLE IF NOT EXISTS digraph_slugs (
    slug_id INTEGER PRIMARY KEY,
	slug_url TEXT NOT NULL,
	slug_noun TEXT NOT NULL
);
EOT;

    /* indexes to create on table */
    const IDX = [
        'CREATE INDEX IF NOT EXISTS digraph_slugs_url_IDX ON digraph_slugs (slug_url);',
        'CREATE INDEX IF NOT EXISTS digraph_slugs_noun_IDX ON digraph_slugs (slug_noun);',
        'CREATE UNIQUE INDEX IF NOT EXISTS digraph_slugs_url_noun_IDX ON digraph_slugs (slug_url,slug_noun);'
    ];

    public function __construct(CMS &$cms)
    {
        parent::__construct($cms);
        $this->pdo = $this->cms->pdo();
        //ensure that table and indexes exist
        $this->pdo->exec(static::DDL);
        foreach (static::IDX as $idx) {
            $this->pdo->exec($idx);
        }
    }

    protected function sanitizeNoun($noun)
    {
        $noun = strtolower($noun);
        $noun = preg_replace('/[^a-z0-9]/', '', $noun);
        return $noun;
    }

    protected function sanitizeSlug($slug)
    {
        $slug = trim($slug, "\/ \t\n\r\0\x0B");
        $slug = preg_replace('/[^a-z0-9\/'.preg_quote(static::CHARS).']+/i', '-', $slug);
        $slug = preg_replace('/\-?\/\-?/', '/', $slug);
        $slug = preg_replace('/\/+/', '/', $slug);
        $slug = preg_replace('/\-+/', '-', $slug);
        $slug = preg_replace('/[\/\-]+$/', '', $slug);
        $slug = preg_replace('/^[\/\-]+/', '', $slug);
        $slug = preg_replace('/^home\//', '', $slug);
        $slug = strtolower($slug);
        return $slug;
    }

    /**
     * Get an array of all nouns associated with a given URL
     */
    public function nouns(string $slug)
    {
        if (!($slug = $this->sanitizeSlug($slug))) {
            return [];
        }
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_slugs WHERE slug_url = :slug ORDER BY slug_id desc'
        );
        if ($s->execute([':slug'=>$slug])) {
            return array_map(
                function ($e) {
                    return $e['slug_noun'];
                },
                $s->fetchAll(\PDO::FETCH_ASSOC)
            );
        }
        return [];
    }

    /**
     * Get an array of all nouns associated with a given URL
     */
    public function slugs(string $noun)
    {
        if (!($noun = $this->sanitizeNoun($noun))) {
            return [];
        }
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_slugs WHERE slug_noun = :noun ORDER BY slug_id desc'
        );
        if ($s->execute([':noun'=>$noun])) {
            return array_map(
                function ($e) {
                    return $e['slug_url'];
                },
                $s->fetchAll(\PDO::FETCH_ASSOC)
            );
        }
        return [];
    }

    /**
     * create a new edge -- removes existing edges so that this newest one will
     * take precedence. The $lazy flag will skip this step so it's faster, but
     * will not necessarily make the requested combination the default for the
     * specified noun.
     */
    public function create(string $url, string $noun, $lazy = false)
    {
        if (!($url = $this->sanitizeSlug($url))) {
            return false;
        }
        if (!($noun = $this->sanitizeNoun($noun))) {
            return false;
        }
        //first remove this url/noun combination if it exists
        if (!$lazy) {
            $this->delete($url, $noun);
        }
        //need to make a new slug entry
        $s = $this->pdo->prepare(
            'INSERT INTO digraph_slugs (slug_url,slug_noun) VALUES (:url,:noun)'
        );
        return $s->execute([':url'=>$url,':noun'=>$noun]);
    }

    /**
     * Delete the specified url/noun slug combination.
     */
    public function delete(string $url, string $noun)
    {
        if (!($url = $this->sanitizeSlug($url))) {
            return false;
        }
        if (!($noun = $this->sanitizeNoun($noun))) {
            return false;
        }
        $s = $this->pdo->prepare(
            'DELETE FROM digraph_slugs WHERE slug_url = :url AND slug_noun = :noun'
        );
        return $s->execute([':url'=>$url,':noun'=>$noun]);
    }
}
