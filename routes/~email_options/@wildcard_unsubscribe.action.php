<?php

use DigraphCMS\Context;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Notifications;
use DigraphCMS\URL\URL;

Breadcrumb::setTopName('Email unsubscribe');

/** @var array<int,string> email addresses to be managed with this form */
$addresses = [];

// only allow access with valid email ID or by being signed in
$email = Emails::get(Context::url()->actionSuffix());
if (!$email) throw new HttpError(404);
if ($email->isService()) throw new HttpError(404);
if ($user = $email->toUser()) {
    $addresses = $user->emails();
}
$addresses[] = $email->to();

// set breadcrumb to use this email ID
Breadcrumb::parent(new URL('/~email_options/?msg=' . $email->uuid()));

// print page title
echo "<h1>Email unsubscribe</h1>";

// print list of addresses being managed
$addresses = array_unique($addresses);
Notifications::printNotice(
    sprintf(
        'Email unsubscribe form for: %s',
        implode(', ', array_map(
            function ($email) {
                return '<code>' . $email . '</code>';
            },
            $addresses
        ))
    )
);

// one-click unsubscribe from this category
echo '<h2>Unsubscribe from ' . $email->categoryLabel() . '</h2>';
echo '<p>' . $email->categoryDescription() . '</p>';

// check if all addresses are already unsubscribed
$unsubscribed = true;
foreach ($addresses as $address) {
    if (!Emails::isUnsubscribed($address, $email->category())) {
        $unsubscribed = false;
        break;
    }
}

// display either unsubscribe or resubscribe button
echo "<div class='navigation-frame navigation-frame--stateless' id='email-unsubscribe-form' data-target='frame'>";
if ($unsubscribed) {
    Notifications::printConfirmation('You are unsubscribed from these emails');
    echo new SingleButton(
        'Resubscribe',
        function () use ($addresses, $email) {
            foreach ($addresses as $address) {
                Emails::resubscribe($address, $email->category());
            }
        }
    );
} else {
    echo new SingleButton(
        'Unsubscribe',
        function () use ($addresses, $email) {
            foreach ($addresses as $address) {
                Emails::unsubscribe($address, $email->category());
            }
        }
    );
}
echo "</div>";
