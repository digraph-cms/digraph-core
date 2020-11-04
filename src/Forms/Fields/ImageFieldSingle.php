<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Forms\Fields;

use Digraph\CMS;
use Formward\FieldInterface;

class ImageFieldSingle extends FileStoreFieldSingle
{
    public function __construct(string $label, string $name = null, FieldInterface $parent = null, CMS $cms = null, string $path = null, array $exts = null, int $maxSize = null)
    {
        parent::__construct($label, $name, $parent, $cms, $path, $exts, $maxSize);
        $this['upload']->attr('accept', 'image/*');
        $this->addValidatorFunction('valid-image', function () {
            $value = $this['upload']->value();
            if (!$value) {
                return true;
            }
            $valid = true;
            if (!preg_match('/^image\//', $value['type'])) {
                $valid = false;
            } elseif (!preg_match('/\.(jpe?g|gif|png|webp|tiff?|w?bmp)$/i', $value['name'])) {
                $valid = false;
            } else {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mime = $finfo->file($value['file']);
                if (!preg_match('/^image\//', $mime)) {
                    $valid = false;
                }
            }
            return $valid ? true : "Error uploading file. Please ensure that it is a valid image file.";
        });
    }
}
