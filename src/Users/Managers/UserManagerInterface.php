<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users\Managers;

use Digraph\CMS;
use Digraph\Users\UserInterface;

interface UserManagerInterface
{
    public function __construct(CMS &$cms);
    public function getByIdentifier(string $identifier) : ?UserInterface;
    public function getByEmail(string $email) : ?UserInterface;
    public function name(string $set) : string;
}
