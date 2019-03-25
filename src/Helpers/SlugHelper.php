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
        $h->noun_register('parent:update', [$this,'updateSlug'], 'slug/updateSlug');
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
            $noun = $this->sanitizeNoun($noun);
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
        $vars['cdate'] = '[cdate-year][cdate-month][cdate-day]';
        $vars['cdate-time'] = '[cdate-hour][cdate-minute]';
        $vars['cdate-year'] = date('Y', $noun['dso.created.date']);
        $vars['cdate-month'] = date('m', $noun['dso.created.date']);
        $vars['cdate-day'] = date('d', $noun['dso.created.date']);
        $vars['cdate-hour'] = date('H', $noun['dso.created.date']);
        $vars['cdate-minute'] = date('i', $noun['dso.created.date']);
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
        $slug = trim($slug, "\/ \t\n\r\0\x0B");
        $slug = preg_replace('/[^a-z0-9\/'.preg_quote(static::CHARS).']+/i', '-', $slug);
        $slug = preg_replace('/\-?\/\-?/', '/', $slug);
        $slug = preg_replace('/\/+/', '/', $slug);
        $slug = preg_replace('/\-+/', '-', $slug);
        $slug = preg_replace('/[\/\-]+$/', '', $slug);
        $slug = preg_replace('/^[\/\-]+/', '', $slug);
        $slug = preg_replace('/^home\//', '', $slug);
        $slug = strtolower($slug);
        //append number if slug exists already for a different noun
        $finalslug = $slug;
        $i = 1;
        while (($nouns = $this->nouns($finalslug)) && !in_array($noun['dso.id'], $nouns)) {
            $i++;
            $finalslug = $slug.'-'.$i;
        }
        //return result
        return $finalslug;
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
