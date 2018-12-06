<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Modules\digraph_core_types;

class Version extends Page
{
    const ROUTING_NOUNS = ['version'];

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
