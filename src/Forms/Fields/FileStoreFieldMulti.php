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
class FileStoreFieldMulti extends \Formward\Fields\Container
{
    protected $cms;
    protected $noun;
    protected $path;

    /**
     * Extra args:
     * string $path the filestore path to use
     * array $exts an array of allowed file extensions
     * int $maxSize the maximum file size (per file) in bytes
     */
    public function __construct(string $label, string $name=null, FieldInterface $parent=null, CMS &$cms=null, string $path=null, array $exts=null, int $maxSize=null)
    {
        if (!$path) {
            $path = 'filefield';
        }
        $this->path = $path;
        $this->cms = $cms;
        $s = $cms->helper('strings');
        parent::__construct($label, $name, $parent);
        //current file
        $this['current'] = new \Formward\Fields\Ordering(
            $s->string('forms.file.upload_multi.current')
        );
        $this['current']->allowDeletion(true);
        //upload new file
        $this['upload'] = new \Formward\Fields\FileMulti(
            $s->string('forms.file.upload_multi.upload')
        );
        //set up tips for indicating max upload rules
        $this['upload']->addTip(
            $s->string(
                'forms.file.tips.limit_count',
                [ini_get('max_file_uploads')]
            ),
            'limit_count'
        );
        $this['upload']->addTip(
            $s->string(
                'forms.file.tips.limit_size_each',
                [ini_get('upload_max_filesize')]
            ),
            'maxsize'
        );
        $this['upload']->addTip(
            $s->string(
                'forms.file.tips.limit_size_total',
                [ini_get('post_max_size')]
            ),
            'limit_size_total'
        );
        //set up extension validator
        if ($exts) {
            $this->allowedExts($exts);
        }
        //set up size validator
        if ($maxSize) {
            $this->maxSize($maxSize);
        }
    }

    public function maxSize($size)
    {
        $s = $this->cms->helper('strings');
        $this['upload']->addTip(
            $s->string(
                'forms.file.max_size',
                ['size'=>$s->filesizeHTML($size)]
            ),
            'maxsize'
        );
        $this['upload']->addValidatorFunction('maxSize', function (&$field) use ($size,$s) {
            if (!$field->value()) {
                return true;
            }
            if ($field->value()['size'] > $size) {
                return $s->string('forms.file.max_size_error', ['max'=>$s->filesizeHTML($size)]);
            }
            return true;
        });
    }

    public function allowedExts($exts)
    {
        $s = $this->cms->helper('strings');
        asort($exts);
        $this['upload']->addTip(
            $s->string(
                'forms.file.allowed_extensions',
                ['exts' => implode(', ', $exts)]
            ),
            'allowedExts'
        );
        $this['upload']->addValidatorFunction('allowedExts', function (&$field) use ($exts,$s) {
            if (!$field->value()) {
                return true;
            }
            $ext = $field->value()['name'];
            if (strpos($ext, '.') === false) {
                return $s->string('forms.file.extension_required');
            }
            $ext = strtolower(preg_replace('/.*\./', '', $ext));
            if (!in_array($ext, $exts)) {
                return $s->string('forms.file.extension_invalid', ['ext'=>$ext]);
            }
            return true;
        });
    }

    protected function nounValue()
    {
        if ($this->noun) {
            $fs = $this->cms->helper('filestore');
            return $fs->list($this->noun, $this->path);
        }
        return null;
    }

    public function value($set = null)
    {
        $value = $this->nounValue();
        $value = $value + $this['upload']->value();
        return $value;
    }

    public function hook_formWrite(Noun &$noun, array $map)
    {
        $fs = $this->cms->helper('filestore');
        /*
        use the 'current' field to do any deletions
         */
        foreach ($this['current']->deleted() as $uniqid) {
            $fs->delete($noun, $uniqid);
        }
        /*
        use the 'current' field to set the order of the array in the filestore
        field of the noun
         */
        $arr = [];
        foreach ($this['current']->value() as $uniqid) {
            $arr[$uniqid] = $noun['filestore.'.$this->path.'.'.$uniqid];
        }
        unset($noun['filestore.'.$this->path]);
        $noun['filestore.'.$this->path] = $arr;
        $noun->update(true);
        /*
        save uploaded files to the noun using the filestore helper
         */
        if ($upload = $this['upload']->value()) {
            //only import file if value is an array, because this means it's a
            //new upload -- otherwise it's a FileStoreFile representing a file
            //that's already in the object
            if (is_array($upload)) {
                foreach ($upload as $f) {
                    $fs->import($this->noun, $f, $this->path);
                }
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
        if ($files = $this->nounValue()) {
            $opts = [];
            foreach ($files as $file) {
                $opts[$file->uniqid()]= $file->metaCard(false);
            }
            $this['current']->opts($opts);
        }
    }

    public function default($set = null)
    {
        return parent::default();
    }
}
