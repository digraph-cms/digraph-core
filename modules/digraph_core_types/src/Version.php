<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

use League\HTMLToMarkdown\HtmlConverter;

class Version extends Page
{
    const ROUTING_NOUNS = ['version'];
    const UNDIFFABLE_TAGS = [
        'table' => 'table',
        'img' => 'image'
    ];

    public function searchIndexed()
    {
        return false;
    }

    /**
     * Needs to produce a markdown version of this version's content, which will
     * be used in the diff verb to produce diffs. It's fine to strip content
     * that cannot be accurately represented as markdown.
     */
    public function bodyDiffable()
    {
        return \Soundasleep\Html2Text::convert(
            $this->body(),
            [
                'ignore_errors' => true
            ]
        );
    }

    public function parentUrl($verb = 'display')
    {
        if ($verb == 'display') {
            if (($parent = $this->parent()) instanceof Versioned) {
                return $parent->url('versions');
            }
        }
        return parent::parentUrl($verb);
    }

    public function effectiveDate()
    {
        return $this['dso.created.date'];
    }

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        $map = parent::formMap($action);
        $map['digraph_name']['default'] = $s->date(time());
        $map['digraph_name']['label'] = $s->string('version.revision_note');
        $map['digraph_title']['required'] = true;
        $map['digraph_title']['label'] = $s->string('version.display_title');
        $map['digraph_slug'] = false;
        if ($action == 'add') {
            if ($parent = $this->cms()->package()->noun()) {
                $map['digraph_title']['default'] = $parent->title();
                if (method_exists($parent, 'currentVersion')) {
                    if ($prev = $parent->currentVersion()) {
                        //confirmation indicating field is prepopulated from previous version
                        $this->factory->cms()->helper('notifications')->confirmation(
                            $s->string('version.confirm_prepopulated')
                        );
                        $map['digraph_title']['default'] = $prev->title();
                        $map['digraph_body']['default'] = $prev['digraph.body'];
                    }
                } else {
                    //error indicating that parent isn't a versioned type
                    $this->factory->cms()->helper('notifications')->warning(
                        $s->string('version.warning_unversionedparent')
                    );
                }
            }
        }
        return $map;
    }
}
