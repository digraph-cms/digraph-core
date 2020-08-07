<?php
/* Digraph Core | https://github.com/jobyone/digraph-core | MIT License */
namespace Digraph\Permissions;

use Digraph\DSO\Noun;
use Digraph\Helpers\AbstractHelper;
use Digraph\Urls\Url;

class PermissionsHelper extends AbstractHelper
{
    protected $urlCache = [];

    /**
     * Check whether a user is allowed to access a particular URL. Will also
     * run a special check on add permissions if this is a proper noun's "add"
     * verb. The permissions this method checks are controlled through the
     * config permissions.url
     *
     * Returns true/false for a definitive result, null for undefined (which
     * should probably generally be treated as false).
     *
     * @param Url $url
     * @param string $userID
     * @return boolean|null
     */
    public function checkUrl(Url $url, string $userID = null): ?bool
    {
        $id = md5(serialize([$url,$userID]));
        if (!isset($this->urlCache[$id])) {
            $this->urlCache[$id] = $this->doCheckUrl($url,$userID);
        }
        return $this->urlCache[$id];
    }

    public function doCheckUrl(Url $url, string $userID = null): ?bool
    {
        $paths = [];
        $noun = $this->cms->helper('urls')->noun($url);
        if ($noun) {
            // check based on specific noun
            $paths[] = $noun['dso.id']. '/' . $url['verb'];
            // check based on dso type
            $paths[] = $noun['dso.type']. '/' . $url['verb'];
        } else {
            // check using url type
            $paths[] = $url['noun']. '/' . $url['verb'];
        }
        // check all paths in decreasing order of specificity
        $output = null;
        foreach (array_reverse($paths) as $path) {
            $result = $this->check($path,'url',$userID);
            // returns the first non-null result we get
            if ($result !== null) {
                // if there is a noun and the verb is add, we also need to checkAddPermissions
                if ($result && $noun && $url['verb'] == 'add') {
                    $result = $this->checkAddPermissions($noun, $url['args']['type'], $userID);
                }
                // otherwise we can just return the result
                $output = $result;
            }
        }
        return $output;
    }

    /**
     * What is allowed to be added where is controlled in two places:
     *
     * First the user must have general permissions to the add verb of the
     * parent noun, controlled through permissions.url.[parent type]/add
     *
     * Second, the user must have specific permissions to add the child type,
     * to the parent type, which is controlled by
     * permissions.add.[parent type]/[child type]
     */
    public function checkAddPermissions($parentOrType, $type, string $userID = null): bool
    {
        if ($parentOrType instanceof Noun) {
            $parentType = $parentOrType['dso.type'];
        } else {
            $parentType = $parentOrType;
        }
        return
        //user must have all the following permissions
        $this->check($parentType . '/add', 'url', $userID) && //url add verb permission
        $this->check($parentType . '/' . $type, 'add', $userID); //add type under parent type
    }

    /**
     * Check a given path/category with a given URL. Returns true/false for
     * definitive results, null if no matching permissions were returned.
     *
     * @param string $path
     * @param string $category
     * @param string $userID
     * @return boolean|null
     */
    public function check(string $path, string $category = 'url', string $userID = null): ?bool
    {
        $allow = null;
        $rules = @$this->cms->config['permissions'][$category];
        if ($userID === null) {
            $userID = $this->cms->helper('users')->id();
        }
        $groups = $this->cms->helper('users')->groups($userID);
        //short-circuit for root user
        if ($userID == 'root@system' || in_array('root', $groups)) {
            return true;
        }
        //check rules
        if ($rules) {
            $path = explode('/', $path);
            $matchingKeys = [];
            $n = 0;
            foreach ($path as $i) {
                $n++;
                if (!$matchingKeys) {
                    $matchingKeys = ['*', $i];
                } else {
                    foreach ($matchingKeys as $k) {
                        $matchingKeys[] = $k . '/*';
                        $matchingKeys[] = $k . '/' . $i;
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
        $rule = trim($rule);
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
