<?php
/* Digraph Core | https://gitlab.com/byjoby/digraph-core | MIT License */
namespace Digraph\Users;

use Digraph\CMS;
use Destructr\DSO;
use Flatrr\FlatArrayInterface;

interface UserInterface extends FlatArrayInterface
{
    public function managerName(string $set = null) : string;
    public function identifier() : string;
    public function id() : string;
    public function name(string $set = null) : string;
    public function __toString();

    //retrieve current primary email address
    public function email() : ?string;
    //add an email address -- does nothing if email is already added
    public function addEmail(string $email);
    //remove current/pending use of an email address
    public function removeEmail(string $email);
    //get the token associated with a pending email
    public function getEmailToken() : ?string;
    //check if a token matches the pending email token
    public function checkEmailToken(string $token) : bool;
    //make an email primary and verified
    public function verifyEmail(string $email);
    //get a pending email address if there is one
    public function pendingEmail() : ?string;
    //get the timestamp a pending email address was added
    public function pendingEmailTime() : ?int;

    public function setPassword(string $password);
    public function checkPassword(string $password) : bool;
    public function insert() : bool;
    public function update() : bool;
    public function delete() : bool;
}
