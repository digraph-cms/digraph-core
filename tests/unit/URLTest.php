<?php

use DigraphCMS\Context;
use DigraphCMS\URL\URL;
use DigraphCMS\URL\URLs;

class URLTest extends \Codeception\Test\Unit
{
    protected function _before()
    {
        $_SERVER['HTTP_HOST'] = 'www.test.com';
        URLs::_init($_SERVER);
        URLs::$sitePath = '/digraph';
        Context::clear();
    }

    protected function _after()
    {
    }

    public function testStaticInitialization()
    {
        $this->assertEquals('http://www.test.com/digraph', URLs::site());
        URLs::_init([
            'HTTP_HOST' => 'www.test.com',
            'SCRIPT_NAME' => '/index.php'
        ]);
        $this->assertEquals('http://www.test.com', URLs::site());
    }

    public function testPathParsing()
    {
        // both slash and blank should yield the site URL
        $this->assertEquals(
            'http://www.test.com/digraph/',
            (new URL('/'))->__toString()
        );
        $this->assertEquals(
            'http://www.test.com/digraph/',
            (new URL(''))->__toString()
        );
        // should parse out site paths
        $this->assertEquals(
            'http://www.test.com/digraph/path/file',
            (new URL('/path/file'))->__toString()
        );
        // with no context relative paths should be relative to site
        $this->assertEquals(
            'http://www.test.com/digraph/path/file',
            (new URL('path/file'))->__toString()
        );
    }

    public function testQueryParsing()
    {
        // should parse out query string with paths and not
        $this->assertEquals(
            'http://www.test.com/digraph/?foo=bar',
            (new URL('?foo=bar'))->__toString()
        );
        $this->assertEquals(
            'http://www.test.com/digraph/path/file?foo=bar',
            (new URL('/path/file?foo=bar'))->__toString()
        );
        // args should be in order
        $this->assertEquals(
            'http://www.test.com/digraph/path/file?foo=bar&zoo=zar',
            (new URL('/path/file?zoo=zar&foo=bar'))->__toString()
        );
        // args should still be in order after setting post-construction
        $url = new URL('/path/file.html');
        $url->query(['z' => 'a', 'a' => 'z']);
        $url->normalize();
        $this->assertEquals(
            'http://www.test.com/digraph/path/file.html?a=z&z=a',
            $url->__toString()
        );
    }

    public function testContextStack()
    {
        // context should be equal to site by default
        $this->assertEquals(
            URLs::site() . '/',
            Context::url()
        );
        // setting/clearing context
        Context::beginUrlContext(new URL('/foo/'));
        $this->assertEquals(
            'http://www.test.com/digraph/foo/',
            Context::url()
        );
        Context::beginUrlContext(new URL('/foo/bar/baz'));
        $this->assertEquals(
            'http://www.test.com/digraph/foo/bar/baz',
            Context::url()
        );
        Context::end();
        $this->assertEquals(
            'http://www.test.com/digraph/foo/',
            Context::url()
        );
        Context::end();
        $this->assertEquals(
            URLs::site() . '/',
            Context::url()
        );
    }

    public function testContextTraversal()
    {
        // .. relative to site root does nothing
        $this->assertEquals(
            'http://www.test.com/digraph/',
            (new URL('..'))->__toString()
        );
        // context with a file at the end
        Context::beginUrlContext(new URL('/a/b/c/d/e/f'));
        $this->assertEquals(
            'http://www.test.com/digraph/a/b/c/d/',
            (new URL('..'))->__toString()
        );
        $this->assertEquals(
            'http://www.test.com/digraph/a/b/c/d/',
            (new URL('../'))->__toString()
        );
        $this->assertEquals(
            'http://www.test.com/digraph/a/b/c/',
            (new URL('../..'))->__toString()
        );
        $this->assertEquals(
            'http://www.test.com/digraph/a/b/c/',
            (new URL('../../'))->__toString()
        );
        // context with a directory at the end
        Context::beginUrlContext(new URL('/a/b/c/d/e/f/'));
        $this->assertEquals(
            'http://www.test.com/digraph/a/b/c/d/e/',
            (new URL('..'))->__toString()
        );
        $this->assertEquals(
            'http://www.test.com/digraph/a/b/c/d/',
            (new URL('../..'))->__toString()
        );
        // also test adding a new path after ..s
        $this->assertEquals(
            'http://www.test.com/digraph/a/b/c/d/foo',
            (new URL('../../foo'))->__toString()
        );
    }

    public function testPartialQueryParsing()
    {
        Context::beginUrlContext(new URL('/foo/bar?baz=buzz'));
        $this->assertEquals(
            'http://www.test.com/digraph/foo/bar?baz=buzz&caz=cuzz',
            (new URL('&caz=cuzz'))->__toString()
        );
    }
}