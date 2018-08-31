<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Permissions;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use Digraph\DSO\Noun;

class PermissionsHelper extends AbstractHelper
{
    public function checkUrl(Url $url) : bool
    {
        $path = '';
        $noun = $this->cms->helper('urls')->noun($url);
        if ($noun) {
            //use dso type as start of path
            $path = $noun['dso.type'];
        } else {
            //use url noun
            $path = $url['noun'];
        }
        $path .= '/'.$url['verb'];
        return $this->check($path) && (!$noun || $this->checkNoun($noun, $url['verb']));
    }

    public function checkNoun(Noun &$noun, string $verb) : bool
    {
        if (method_exists($noun, 'checkPermissions')) {
            return $noun->checkPermissions($this->cms->helper('users')->user(), $verb);
        }
        return true;
    }

    public function check(string $path, string $category='url') : bool
    {
        $allow = false;
        $rules = @$this->cms->config['permissions'][$category];
        if ($rules) {
            $path = explode('/', $path);
            $matchingKeys = [];
            $n = 0;
            foreach ($path as $i) {
                $n++;
                if (!$matchingKeys) {
                    $matchingKeys = ['*',$i];
                } else {
                    foreach ($matchingKeys as $k) {
                        $matchingKeys[] = $k.'/*';
                        $matchingKeys[] = $k.'/'.$i;
                    }
                    $matchingKeys = array_unique($matchingKeys);
                }
            }
            foreach ($matchingKeys as $key) {
                if (isset($rules[$key])) {
                    foreach ($rules[$key] as $rule) {
                        $new = $this->checkRule($rule);
                        if ($new !== null) {
                            $allow = $new;
                        }
                    }
                }
            }
        }
        //always allow root user
        if (!$allow) {
            if ($this->cms->helper('users')->userName() == 'root') {
                $this->cms->log('permissions denial skipped for root user');
                $allow = true;
            }
        }
        return $allow;
    }

    public function checkRule($rule)
    {
        $rule = strtolower(trim($rule));
        if ($rule == 'allow all') {
            return true;
        } elseif ($rule == 'deny all') {
            return false;
        } else {
            if ($this->cms->helper('users')->userID()) {
                list($mode, $type, $list) = explode(' ', $rule, 3);
                $list = preg_split('/ *, */', $list);
                if ($type == 'user') {
                    if (in_array($this->cms->helper('users')->userName(), $list)) {
                        return $mode == 'allow';
                    }
                }
                if ($type == 'group') {
                    foreach ($this->cms->helper('users')->userGroups() as $group) {
                        if (in_array($group, $list)) {
                            return $mode == 'allow';
                        }
                    }
                }
            }
        }
        return null;
    }
}
