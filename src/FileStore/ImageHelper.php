<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\FileStore;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;

class ImageHelper extends AbstractHelper
{
    public function supports($ext)
    {
        return in_array(strtolower($ext), [
            'bmp',
            'gif',
            'jpeg',
            'jpg',
            'png',
        ]);
    }

    protected function imagine_box($rules)
    {
        $size = explode(' ', $rules['size']);
        return new \Imagine\Image\Box($size[0], $size[1]);
    }

    protected function imagine_boxmode($rules)
    {
        switch (explode(' ', $rules['size'])[2]) {
            case 'inset':
                return \Imagine\Image\ImageInterface::THUMBNAIL_INSET;
            case 'cover':
                return \Imagine\Image\ImageInterface::THUMBNAIL_OUTBOUND;
        }
    }

    protected function imagine_size(&$image, $rules)
    {
        $box = $this->imagine_box($rules);
        $mode = $this->imagine_boxmode($rules);
        return $image->thumbnail($box, $mode);
    }

    protected function imagine_driver()
    {
        switch ($this->cms->config['image.driver']) {
            case 'gd':
                return new \Imagine\Gd\Imagine();
            case 'imagick':
                return new \Imagine\Imagick\Imagine();
            case 'gmagick':
                return new \Imagine\Gmagick\Imagine();
        }
    }

    public function process($input, $output, $rules)
    {
        $i = $this->imagine_driver();
        //open file
        $image = $i->open($input);
        //set size
        $image = $this->imagine_size($image, $rules);
        //save
        $image->save($output, @$rules['save']?$rules['save']:[]);
        //strip metadata
        if (@$rules['strip_exif']) {
        }
    }

    protected function rules($preset)
    {
        if (!is_array($rules = $this->cms->config['image.presets.'.$preset])) {
            return null;
        }
        $base = $this->cms->config['image.preset_base'];
        foreach ($rules as $key => $value) {
            $base[$key] = $value;
        }
        return $base;
    }

    public function output(&$package, FileStoreFile $file, string $preset=null)
    {
        if ($preset === null) {
            $preset = 'default';
        }
        //verify that preset exists
        if (!($rules = $this->rules($preset))) {
            $package->error(404);
            return;
        }
        //set extension from filename/rules
        $extension = @$rules['extension']?$rules['extension']:preg_replace('/.+\./', '', $file->name());
        //set up the cache information we'll need
        //note that cache ID is based on preset and file hash, so the image
        //cache will be effectively deduplicated
        $cacheID = md5(serialize([$file->hash(),$preset])).'.'.$extension;
        $cacheDir = $this->cms->config['image.cache.dir'];
        if ($cacheDir && (is_dir($cacheDir) || mkdir($cacheDir))) {
            $cacheFile = $this->cms->config['image.cache.dir'].'/'.$cacheID;
            $cacheable = true;
        } else {
            $cacheFile = sys_get_temp_dir().'/'.uniqid().'.'.$extension;
            $cacheable = false;
        }
        $cacheExpired = time()-$this->cms->config['image.cache.ttl'];
        if ($package['noun.dso.modified.date'] > $cacheExpired) {
            $cacheExpired = $package['noun.dso.modified.date'];
        }
        //check if cache file exists and is not expired
        if (!is_file($cacheFile) || filemtime($cacheFile) < $cacheExpired) {
            //output file needs to be built
            $this->process($file->path(), $cacheFile, $rules);
        }
        //output
        $filename = (($preset !== 'default')?$preset.'_':'').$file->nameWithHash();
        $originalExtension = preg_replace('/.+\./', '', $filename);
        if ($extension != $originalExtension) {
            $filename .= '.'.$extension;
        }
        $package->makeMediaFile($filename);
        $package['response.outputmode'] = 'readfile';
        $package['response.readfile'] = $cacheFile;
        unset($package['response.content']);
        $package['response.cacheable'] = $cacheable;
        $package['response.ttl'] = $this->cms->config['media.package.response.ttl'];
        $package['response.last-modified'] = $file->time();
    }
}
