<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\CMS;
use Destructr\DSO;

interface UserInterface
{
    public function identifier() : string;
    public function name() : string;
    public function email() : ?string;
    public function setPassword(string $password);
    public function checkPassword(string $password) : bool;
    public function addEmail(string $email, bool $skipVerification = false);
    public function removeEmail(string $email);
    public function create() : bool;
    public function update() : bool;
    public function delete() : bool;
}
