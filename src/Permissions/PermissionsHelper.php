<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Permissions;

use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;
use Digraph\DSO\Noun;

class PermissionsHelper extends AbstractHelper
{
    public function checkUrl(Url $url, string $userID = null) : bool
    {
        $path = '';
        $noun = $this->cms->helper('urls')->noun($url);
        if ($noun) {
            //pass off to checkAddPermissions if verb is "add"
            if ($url['verb'] == 'add') {
                return $this->checkAddPermissions($noun, $url['args']['type'], $userID);
            }
            //use dso type as start of path
            $path = $noun['dso.type'];
        } else {
            //use url noun
            $path = $url['noun'];
        }
        $path .= '/'.$url['verb'];
        return $this->check($path, 'url', $userID);
    }

    public function checkAddPermissions(&$parentOrType, $type, string $userID=null) : bool
    {
        if ($parentOrType instanceof Noun) {
            $parentType = $parentOrType['dso.type'];
        } else {
            $parentType = $parentOrType;
        }
        return
            //user must have all the following permissions
            $this->check($parentType.'/add', 'url', $userID) &&//url add verb permission
            $this->check($parentType.'/'.$type, 'add', $userID);//add type under parent type
    }

    public function check(string $path, string $category='url', string $userID = null) : bool
    {
        $allow = false;
        $rules = @$this->cms->config['permissions'][$category];
        if ($userID === null) {
            $userID = $this->cms->helper('users')->id();
        }
        $groups = $this->cms->helper('users')->groups($userID);
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
                        $new = $this->checkRule($rule, $userID, $groups);
                        if ($new !== null) {
                            $allow = $new;
                        }
                    }
                }
            }
        }
        return $allow;
    }

    protected function checkRule($rule, $userID, $groups)
    {
        $rule = strtolower(trim($rule));
        if ($rule == 'allow all') {
            return true;
        } elseif ($rule == 'deny all') {
            return false;
        } else {
            if ($userID) {
                list($mode, $type, $list) = explode(' ', $rule, 3);
                $list = preg_split('/ *, */', $list);
                if ($type == 'user') {
                    if (in_array($userID, $list)) {
                        return $mode == 'allow';
                    }
                }
                if ($type == 'group') {
                    foreach ($groups as $group) {
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
