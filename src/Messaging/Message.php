<?php
namespace DigraphCMS\Messaging;

class Message {
    protected $uuid, $subject, $sender, $recipient, $body, $time;
    protected $read, $archived, $important, $sensitive, $email;
}