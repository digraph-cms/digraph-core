<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

use Digraph\DSO\Noun;

/**
 * SlugHelper manages the non-canonical URLs that nouns can be found at. The
 * default behavior of this helper is to keep all past slugs of any given noun
 * indefinitely. That way all past URLs of a given noun will keep working, even
 * if you change them later.
 */
class SlugHelper extends \Digraph\Helpers\AbstractHelper
{
    const CHARS = '$-_.+!*(),';

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

    public function hook_export(&$export)
    {
        $slugs = [];
        foreach ($export['noun_ids'] as $noun) {
            if ($these = $this->slugs($noun)) {
                $slugs[$noun] = $these;
            }
        }
        return $slugs;
    }

    public function hook_import($data, $nouns)
    {
        $log = [];
        foreach ($data['helper']['slugs'] as $noun => $slugs) {
            //first regenerate slug from pattern
            $this->updateSlug($noun);
            //reverse order, so that lower-priority slugs get added first, and
            //the priority matches up after reimporting
            foreach (array_reverse($slugs) as $slug) {
                if ($this->create($slug, $noun, true)) {
                    $log[] = "New Slug: $slug =&gt; $noun";
                }
            }
        }
        return $log;
    }

    public function initialize()
    {
        /*
        Set up hooks so that slugs are deleted on permanent deletion, or when
        a noun or any of its parents are updated.
        We don't update slugs on the normal insert trigger, because they might
        not have parents yet at that point, so we need to have a different
        trigger that gets called manually by add-type forms.
         */
        $h = $this->cms->helper('hooks');
        $h->noun_register('delete_permanent', [$this,'deleteAll'], 'slug/deleteAll');
        $h->noun_register('update', [$this,'updateSlug'], 'slug/updateSlug');
        $h->noun_register('added', [$this,'updateSlug'], 'slug/updateSlug');
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

    public function updateSlug($noun)
    {
        if (!($noun instanceof Noun)) {
            $noun = $this->cms->read($noun);
        }
        if (!$noun) {
            return false;
        }
        if ($noun['digraph.slugpattern']) {
            if ($url = $this->createFromPattern($noun['digraph.slugpattern'], $noun)) {
                if ($url != $this->slug($noun['dso.id'])) {
                    return $this->create($url, $noun['dso.id']);
                }
            }
        }
        return true;
    }

    public function createFromPattern($slug, $noun)
    {
        if (!($noun instanceof Noun)) {
            $noun = $this->cms->read($noun);
        }
        if (!$noun) {
            return false;
        }
        //pull vars from parent
        if ($parent = $noun->parent()) {
            $vars['parent'] = $parent->url()['noun'];
            $vars['parentid'] = $parent['dso.id'];
            if (method_exists($parent, 'slugVars')) {
                foreach ($parent->slugVars() as $key => $value) {
                    $vars[$key] = $value;
                }
            }
        }
        // pull vars from noun
        $vars['id'] = $noun['dso.id'];
        $vars['name'] = $noun->name();
        $vars['cdate'] = date('Ymd', $noun['dso.created.date']);
        $vars['cdatetime'] = date('YmdHi', $noun['dso.created.date']);
        $vars['cdate-year'] = date('Y', $noun['dso.created.date']);
        $vars['cdate-month'] = date('m', $noun['dso.created.date']);
        $vars['cdate-day'] = date('d', $noun['dso.created.date']);
        $vars['cdate-hour'] = date('H', $noun['dso.created.date']);
        $vars['cdate-minute'] = date('i', $noun['dso.created.date']);
        if (method_exists($noun, 'slugVars')) {
            foreach ($noun->slugVars() as $key => $value) {
                $vars[$key] = $value;
            }
        }
        //do variable replacement
        $slug = preg_replace_callback(
            '/\[(.+?)\]/',
            function ($m) use ($vars) {
                if (isset($vars[$m[1]])) {
                    return $vars[$m[1]];
                } else {
                    return '';
                }
            },
            $slug
        );
        //clean up
        $slug = $this->sanitizeSlug($slug);
        //append number if slug exists already for a different noun
        $slug = $this->uniqueSlug($slug, $noun['dso.id']);
        //return result
        return $slug;
    }

    protected function sanitizeNoun($noun)
    {
        if ($noun instanceof Noun) {
            return $noun['dso.id'];
        }
        $noun = strtolower($noun);
        $noun = preg_replace('/[^a-z0-9]/', '', $noun);
        return $noun;
    }

    public function uniqueSlug($slug, $noun=null)
    {
        $i = 1;
        $unique = $slug;
        while (($nouns = $this->nouns($unique)) && !in_array($noun, $nouns)) {
            $i++;
            $unique = $slug.'-'.$i;
        }
        return $unique;
    }

    public function sanitizeSlug($slug)
    {
        $slug = strtolower($slug);
        $slug = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $slug);
        $slug = trim($slug, "\/ \t\n\r\0\x0B");
        $slug = preg_replace('/\s+/', '-', $slug);
        $slug = preg_replace('/[^a-z0-9\/'.preg_quote(static::CHARS).']+/i', '', $slug);
        $slug = preg_replace('/\-?\/\-?/', '/', $slug);
        $slug = preg_replace('/\/+/', '/', $slug);
        $slug = preg_replace('/([^a-z0-9])+/', '$1', $slug);
        $slug = preg_replace('/[^a-z0-9]+$/', '', $slug);
        $slug = preg_replace('/^[^a-z0-9]+/', '', $slug);
        $slug = preg_replace('/^home\//', '', $slug);
        $slug = preg_replace('/[^a-z0-9]*([\/\-\_])[^a-z0-9]*/', '$1', $slug);
        return $slug;
    }

    public function list(int $limit, int $offset)
    {
        $args = [];
        $l = '';
        if ($limit) {
            $l .= ' LIMIT :limit';
            $args[':limit'] = $limit;
        }
        if ($offset) {
            $l .= ' OFFSET :offset';
            $args[':offset'] = $offset;
        }
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_slugs ORDER BY slug_id desc'.$l
        );
        if ($s->execute($args)) {
            return $s->fetchAll(\PDO::FETCH_ASSOC);
        }
        return [];
    }

    public function count()
    {
        $s = $this->pdo->prepare(
            'SELECT COUNT(slug_id) FROM digraph_slugs'
        );
        if ($s->execute()) {
            $out = $s->fetchAll(\PDO::FETCH_ASSOC);
            return intval($out[0]['COUNT(slug_id)']);
        }
        return 0;
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
    public function slugs($noun)
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
     * Get the most current slug associated with a given noun
     */
    public function slug($noun)
    {
        if (!($noun = $this->sanitizeNoun($noun))) {
            return [];
        }
        $s = $this->pdo->prepare(
            'SELECT * FROM digraph_slugs WHERE slug_noun = :noun ORDER BY slug_id desc LIMIT 1'
        );
        if ($s->execute([':noun'=>$noun])) {
            return $s->fetch(\PDO::FETCH_ASSOC)['slug_url'];
        }
        return null;
    }

    /**
     * create a new edge -- removes existing edges so that this newest one will
     * take precedence. The $lazy flag will skip this step so it's faster, but
     * will not necessarily make the requested combination the default for the
     * specified noun.
     */
    public function create(string $url, $noun, $lazy = false)
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
    public function delete(string $url, $noun)
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

    /**
     * Delete all slugs associated with the given noun
     */
    public function deleteAll($noun)
    {
        if (!($noun = $this->sanitizeNoun($noun))) {
            return false;
        }
        $s = $this->pdo->prepare(
            'DELETE FROM digraph_slugs WHERE slug_noun = :noun'
        );
        return $s->execute([':noun'=>$noun]);
    }
}
