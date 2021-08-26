<?php

namespace DigraphCMS;

use DigraphCMS\Content\Page;
use DigraphCMS\HTTP\AccessDeniedError;
use DigraphCMS\HTTP\Request;
use DigraphCMS\HTTP\Response;
use DigraphCMS\URL\URL;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\Users;
use Flatrr\SelfReferencingFlatArray;
use Throwable;

class Context
{
    protected static $request, $response, $url, $thrown;
    protected static $data = [];

    public static function url(URL $url = null): URL
    {
        if ($url) {
            static::$url = $url;
        }
        return static::$url;
    }

    /**
     * Require that the current user be a member of a given group. Throws 
     * exception if user is not.
     *
     * @param string $group
     * @return void
     */
    public static function requireGroup(string $group)
    {
        if (!Permissions::inGroup($group, Users::current() ?? Users::guest())) {
            throw new AccessDeniedError("Must be a member of the group $group");
        }
    }

    /**
     * Require that the current user be a member of one of the groups given.
     * Throws an exception if the user is not. 
     *
     * @param string[] $groups
     * @param User $user
     * @return void
     */
    public static function requireGroups(array $groups)
    {
        if (!Permissions::inGroups($groups, Users::current() ?? Users::guest())) {
            throw new AccessDeniedError("Must be a member of one of the groups: " . implode(', ', $groups));
        }
    }

    /**
     * Retrieve an arg from the request URL
     *
     * @param string $key
     * @return mixed
     */
    public static function arg(string $key)
    {
        if (static::$request) {
            return @static::$request->url()->arg($key);
        } else {
            return null;
        }
    }

    public static function fields(): SelfReferencingFlatArray
    {
        if (!static::data('fields')) {
            static::data(
                'fields',
                new SelfReferencingFlatArray(
                    Config::get('fields')
                )
            );
        }
        return static::data('fields');
    }

    public static function request(Request $request = null): ?Request
    {
        if ($request) {
            static::$request = $request;
        }
        return static::$request;
    }

    public static function response(Response $response = null): ?Response
    {
        if ($response) {
            static::$response = $response;
        }
        return static::$response;
    }

    public static function page(Page $page = null): ?Page
    {
        return static::data('page', $page);
    }

    public static function thrown(Throwable $thrown = null): ?Throwable
    {
        if ($thrown) {
            static::$thrown = $thrown;
        }
        return static::$thrown;
    }

    public static function data($name, $value = null)
    {
        end(static::$data);
        $endKey = key(static::$data);
        if ($value !== null) {
            static::$data[$endKey][$name] = $value;
        }
        return @static::$data[$endKey][$name];
    }

    public static function begin()
    {
        static::$data[] = [];
    }

    public static function clone()
    {
        static::$data[] = end(static::$data) ?? [];
    }

    public static function end()
    {
        array_pop(static::$data);
    }
}
