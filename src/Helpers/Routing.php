<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Helpers;

class Routing extends AbstractHelper
{
    public function hookFile(string $type, string $filename, string $module = null)
    {
        //extension files all go in the @hook folder
        $filename = '@hook/'.$filename;
        //from there it works the same as any other file
        return $this->file($type, true, $filename, $module);
    }

    public function allHookFiles(string $type, $filename)
    {
        //extension files all go in the @hook folder
        $filename = '@hook/'.$filename;
        //from there it works the same as any other file
        return $this->allFiles($type, true, $filename);
    }

    public function file(string $type, bool $proper, string $filename, string $module = null)
    {
        //prefer a specific module
        if ($module) {
            foreach ($this->allFiles($type, $proper, $filename) as $file) {
                if ($file['module'] == $module) {
                    return $file;
                }
            }
        }
        //return default
        return @array_shift($this->allFiles($type, $proper, $filename));
    }

    public function allFiles(string $type, bool $proper, string $filename)
    {
        /**
         * Verify that type exists, otherwise use "default"
         */
        if ($proper && strpos($filename, '@') !== 0 && !$this->cms->config['types.content.'.$type]) {
            $type = 'default';
        }
        /**
         * Get a list of all the type names we'll use to search for routes
         */
        if ($class = $this->cms->config['types.content.'.$type]) {
            $types = $class::ROUTING_NOUNS;
            array_unshift($types, $type);
            $types = array_unique($types);
        } else {
            $types = [$type];
        }
        /**
         * Make a list of all candidate filenames
         */
        $candidatesGeneral = array();
        $candidatesSpecific = array();
        $i = 0;
        /*
        Build list of type-specific candidates, ordered by type and then by
        routing path.
         */
        foreach ($types as $type) {
            foreach (array_reverse($this->cms->config['routing.paths']) as $module => $path) {
                if ($proper) {
                    $candidatesSpecific[$module.':specific:'.$i++] = "$path/$type/$filename";
                } else {
                    $candidatesSpecific[$module.':specific:'.$i++] = "$path/$type/@all/$filename";
                    $candidatesSpecific[$module.':specific:'.$i++] = "$path/$type@all/$filename";
                }
                if (preg_match('/^@.+\//', $filename)) {
                    $candidatesSpecific[$module.':specific:'.$i++] = "$path/$type$filename";
                }
            }
        }
        /*
        Build list of general candidates, ordered by routing path.
         */
        foreach (array_reverse($this->cms->config['routing.paths']) as $module => $path) {
            //build general candidates
            if ($proper) {
                $candidatesGeneral[$module.':general:'.$i++] = "$path/@any/$filename";
            } else {
                $candidatesGeneral[$module.':general:'.$i++] = "$path/@any/@all/$filename";
                $candidatesGeneral[$module.':general:'.$i++] = "$path/@any@all/$filename";
            }
        }
        //general candidates shouldn't be used if the type doesn't exist
        //otherwise we've just made basically all URLs valid
        if (!$this->cms->config['types.content.'.$type]) {
            $candidatesGeneral = array();
        }
        //append general to specific candidates, because specific should take priority
        $candidates = array_merge($candidatesSpecific, $candidatesGeneral);
        //search from the front of the array, returning the first matching file
        //this makes specific candidates go first, with later-added search paths taking secondary priority
        $return = array();
        foreach ($candidates as $key => $value) {
            if (is_file($value)) {
                list($module, $type) = explode(':', $key);
                $return[] = [
                    'file' => $value,
                    'module' => $module,
                    'type' => $type
                ];
            }
        }
        //return null if we didn't find anything else
        return $return;
    }
}
