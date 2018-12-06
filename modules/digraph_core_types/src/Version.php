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

    /**
     * Needs to produce a markdown version of this version's content, which will
     * be used in the diff verb to produce diffs. It's fine to strip content
     * that cannot be accurately represented as markdown.
     */
    public function bodyDiffable()
    {
        $body = $this->body();
        //strip undiffable tags
        foreach (static::UNDIFFABLE_TAGS as $tag => $name) {
            $body = preg_replace('/<'.$tag.'.*?>(.*?<\/'.$tag.'.*?>)?/i', '['.$name.' can\'t be reliably diffed]', $body);
        }
        //strip tags and convert to markdown
        $body = strip_tags($body, '<p><br><h1><h2><h3><h4><h5><h6><strong><em><i><b><u>');
        $converter = new HtmlConverter();
        $converter->getConfig()->setOption('header_style', 'atx');
        $body = $converter->convert($body);
        //add spaces to the end of every line
        $body = preg_replace('/(\r?\n)/m', ' $1', $body);
        //return
        return $body;
    }

    public function effectiveDate()
    {
        if (!$this['digraph.published.force'] && $this['digraph.published.start']) {
            return $this['digraph.published.start'];
        }
        return $this['dso.created.date'];
    }

    public function formMap(string $action) : array
    {
        $s = $this->factory->cms()->helper('strings');
        $map = parent::formMap($action);
        $map['000_digraph_name']['default'] = $s->date(time());
        $map['000_digraph_name']['label'] = $s->string('version.revision_note');
        $map['001_digraph_title']['required'] = true;
        $map['001_digraph_title']['label'] = $s->string('version.display_title');
        $map['100_digraph_slug'] = false;
        if ($action == 'add') {
            if ($parent = $this->parent()) {
                $map['001_digraph_title']['default'] = $parent->title();
                if (method_exists($parent, 'currentVersion')) {
                    if ($prev = $parent->currentVersion()) {
                        //confirmation indicating field is prepopulated from previous version
                        $this->factory->cms()->helper('notifications')->confirmation(
                            $s->string('version.confirm_prepopulated')
                        );
                        $map['001_digraph_title']['default'] = $prev->title();
                        $map['500_digraph_body']['default'] = $prev['digraph.body'];
                    }
                } else {
                    //error indicating that parent isn't a versioned type
                    $this->factory->cms()->helper('notifications')->warning(
                        $s->string('version.warning_unversionedparent')
                    );
                }
            } else {
                //error indicating that there isn't a parent
                $this->factory->cms()->helper('notifications')->warning(
                    $s->string('version.warning_noparent')
                );
            }
        }
        return $map;
    }
}
