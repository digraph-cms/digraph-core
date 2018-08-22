<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Media;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;

class MediaHelper extends AbstractHelper
{
    public function get($search)
    {
        $args = [];
        $argString = '';
        if ($search instanceof Url) {
            $args = $search['args'];
            $argString = $search->argString();
            $search = preg_replace('/\/$/', '', $search->pathString());
        }
        foreach (array_reverse($this->cms->config['media.paths']) as $path) {
            $path .= '/'.$search;
            if (is_file($path)) {
                return $this->prepare($path);
            }
        }
    }

    protected function prepare($file, $mime=null, $filename=null)
    {
        if (!$mime) {
            $mime = mime_content_type($file);
        }
        if (!$filename) {
            $filename = basename($file);
        }
        return [
            'path' => $file,
            'mime' => mime_content_type($file),
            'filename' => basename($file)
        ];
    }
}
