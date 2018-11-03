<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Digraph\CMS;
use Digraph\DSO\Noun;
use Formward\AbstractField;
use Formward\FieldInterface;

/**
 * This field works with the FileStore helper to allow uploading of files to
 * Nouns. By default it will put the uploaded file in the filestore path
 * "filefield." To use a different path, specify it in the field's map entry,
 * as the first value of the optional "extraConstructArgs" array.
 *
 * Also note that "field" is not required in the map entry for this Field
 *
 * WARNING: this field will clear out all other uploads in the filestore path it
 * uses -- DO NOT USE IT IN A PATH WHERE YOU KEEP FILES FROM OTHER PLACES
 *
 * For example:
 * ...
 *   500-field-name:
 *     label: File upload
 *     class: Digraph\Forms\Fields\FileStoreFieldSingle
 *     extraConstructArgs:
 *       - my-path-name
 */
class FileStoreFieldSingle extends \Formward\Fields\Container
{
    protected $cms;
    protected $noun;
    protected $path;

    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null, string $path=null)
    {
        if (!$path) {
            $path = 'filefield';
        }
        $this->path = $path;
        $this->cms = $cms;
        $s = $cms->helper('strings');
        parent::__construct($label, $name, $parent);
        //current file
        $this['current'] = new \Formward\Fields\DisplayOnly(
            $s->string('forms.file.upload_single.current')
        );
        //upload new file
        $this['upload'] = new \Formward\Fields\File(
            $s->string('forms.file.upload_single.upload')
        );
    }

    protected function nounValue()
    {
        if ($this->noun) {
            $fs = $this->cms->helper('filestore');
            if ($files = $fs->list($this->noun, $this->path)) {
                return array_pop($files);
            }
        }
        return null;
    }

    public function value($set = null)
    {
        if ($upload = $this['upload']->value()) {
            return $upload;
        }
        return $this->nounValue();
    }

    public function hook_formWrite(Noun &$noun, array $map)
    {
        if ($upload = $this['upload']->value()) {
            //only import file if value is an array, because this means it's a
            //new upload -- otherwise it's a FileStoreFile representing a file
            //that's already in the object
            if (is_array($upload)) {
                $fs = $this->cms->helper('filestore');
                $fs->clear($this->noun, $this->path);
                $fs->import($this->noun, $upload, $this->path);
            }
        }
    }

    public function required($set = null)
    {
        return AbstractField::required($set);
    }

    public function dsoNoun(&$noun)
    {
        $this->noun = $noun;
        if ($file = $this->nounValue()) {
            $this['current']->content($file->metaCard(false));
        }
    }

    public function default($set = null)
    {
        return parent::default();
    }
}
