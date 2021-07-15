<?php

namespace Session;

use DigraphCMS\Session\Session;

// set a very short auth timeout so we can test that
define('SESSION_AUTH_TIMEOUT', 1);
// use a different session name
define('SESSION_COOKIE_NAME', 'TEST');

class SessionTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    protected function _before()
    {
        $_SESSION = [];
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'Fake browser';
        Session::__init();
    }

    protected function _after()
    {
    }

    public function testInit()
    {
        $this->assertEquals(time(), Session::get('start'));
        $this->assertEquals(time(), Session::get('touch'));
        $this->assertEquals(time() + 1, Session::get('auth_expires'));
        $this->assertCount(1, Session::remoteHistory());
        $this->assertCount(1, Session::userHistory());
        // also test remote() here
        $remote = Session::remote();
        $this->assertEquals('127.0.0.1', $remote['REMOTE_ADDR']);
        $this->assertEquals('Fake browser', $remote['HTTP_USER_AGENT']);
    }

    public function testGettersAndSetters()
    {
        Session::set('foo', 'bar');
        $this->assertEquals('bar', Session::get('foo'));
        // shouldn't be in $_SESSION yet
        $this->assertArrayNotHasKey('foo', $_SESSION);
        // should after committing
        Session::__commit();
        $this->assertEquals('bar', $_SESSION['foo']);
    }

    public function testUserChanging()
    {
        // user is initially false
        $this->assertFalse(Session::user());
        // setting user should:
        Session::setUser('user');
        // set user in both Session and $_Session
        $this->assertEquals('user', Session::user());
        $this->assertEquals('user', $_SESSION['user']);
        // deauthorizing should do the same
        Session::deauthorize();
        $this->assertFalse(Session::user());
        $this->assertFalse($_SESSION['user']);
    }

    public function testAutomaticDeauthorization()
    {
        Session::setUser('user');
        Session::__commit();
        // change user agent
        $_SERVER['HTTP_USER_AGENT'] = 'Another fake browser';
        // reinitializing should clear user
        Session::__init();
        $this->assertFalse(Session::user());
    }

    public function testProtectedNamespaces()
    {
        Session::setUser('user');
        $ns = Session::namespace('protected');
        $ns->set('foo', 'bar');
        $this->assertEquals('bar', $ns->get('foo'));
        Session::deauthorize();
        // existing namespace objects should keep working after deauth
        $this->assertEquals('bar', $ns->get('foo'));
        // load namespace again to test
        $ns = Session::namespace('protected');
        $this->assertNull($ns->get('foo'));
    }

    public function testUnprotectedNamespaces()
    {
        Session::setUser('user');
        $ns = Session::namespace('unprotected', true);
        $ns->set('foo', 'bar');
        $this->assertEquals('bar', $ns->get('foo'));
        Session::deauthorize();
        // should still work after deauth
        $this->assertEquals('bar', $ns->get('foo'));
        // load namespace again to test it still works
        $ns = Session::namespace('unprotected', true);
        $this->assertEquals('bar', $ns->get('foo'));
    }

    public function testUnprotectedGuestNamespaces()
    {
        $ns = Session::namespace('unprotected', true);
        $ns->set('foo', 'bar');
        $this->assertEquals('bar', $ns->get('foo'));
        Session::setUser('user');
        // existing namespace objects should keep working after auth
        $this->assertEquals('bar', $ns->get('foo'));
        // load namespace again to test
        $ns = Session::namespace('unprotected', true);
        $this->assertEquals('bar', $ns->get('foo'));
    }

    public function testFlashNamespaces()
    {
        $flash = Session::flashNamespace('test');
        // next and current should be initially empty
        $this->assertCount(0, $flash->next());
        $this->assertCount(0, $flash->current());
        // flash() should set into next()
        $flash->flash('foo', 'bar');
        $this->assertEquals('bar', $flash->next()['foo']);
        // foo shouldn't exist in current()
        $this->assertArrayNotHasKey('foo', $flash->current());
        // simulate reload
        Session::__commit();
        session::__init();
        // flash() should still be set into next()
        $flash->flash('foo', 'bar');
        $this->assertEquals('bar', $flash->next()['foo']);
        // foo still shouldn't exist in current()
        $this->assertArrayNotHasKey('foo', $flash->current());
        // after calling advance() foo should be in current, next should be empty
        $flash->advance();
        $this->assertEquals('bar', $flash->current()['foo']);
        $this->assertCount(0, $flash->next());
        // flash another message
        $flash->flash('baz', 'buzz');
        $this->assertEquals('buzz', $flash->next()['baz']);
        // simulate reload
        Session::__commit();
        session::__init();
        // state should be the same
        $this->assertEquals('bar', $flash->current()['foo']);
        $this->assertEquals('buzz', $flash->next()['baz']);
        // advance again and check
        $flash->advance();
        $this->assertEquals('buzz', $flash->current()['baz']);
        $this->assertCount(0, $flash->next());
        $this->assertCount(1, $flash->current());
    }
}
