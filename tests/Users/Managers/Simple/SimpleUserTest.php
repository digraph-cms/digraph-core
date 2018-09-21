<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
declare(strict_types=1);
namespace Digraph\Users\Managers\Simple;

use PHPUnit\Framework\TestCase;

class SimpleUserTest extends TestCase
{
    public function testPasswords()
    {
        $user = new SimpleUser();
        //shouldn't have a password by default
        $this->assertFalse($user->checkPassword('foo'));
        $this->assertFalse($user->checkPassword(''));
        //setting a password
        $user->setPassword('foo');
        $this->assertTrue($user->checkPassword('foo'));
        $this->assertFalse($user->checkPassword('bar'));
        //changing password
        $user->setPassword('bar');
        $this->assertTrue($user->checkPassword('bar'));
        $this->assertFalse($user->checkPassword('foo'));
    }
}
