<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\Email\Emails;
use DigraphCMS\UI\Breadcrumb;
use DigraphCMS\UI\ToggleButton;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\Users;

Breadcrumb::setTopName('Manage email preferences');

/** @var array<int,string> email addresses to be managed with this form */
$addresses = [];

// only allow access with valid email ID
if ($email = Emails::get(Context::url()->actionSuffix())) {
    if ($user = $email->toUser()) {
        $addresses = $user->emails();
    }
    $addresses[] = $email->to();
    $usr = $email->toUser();
} 
// or if they're signed in, use their account email(s)
elseif ($user = Users::current()) {
    $addresses = $user->emails();
} else {
    Permissions::requireAuth();
}

// print page title
echo "<h1>Email preferences</h1>";

// list of addresses being managed
$addresses = array_unique($addresses);

// display form
$categories = Emails::existingCategories();

echo "<table>";
echo "<tr><th></th>";
echo implode('', array_map(
    function ($email) {
        return "<th><code>$email</code></th>";
    },
    $addresses
));
echo "</tr>";
foreach ($categories as $category) {
    $count = Emails::select()
        ->notErrored()
        ->where('time > ?', strtotime('-1 year'))
        ->where('category = ?', [$category])
        ->where(
            '('
                . implode(' OR ', array_map(
                    function ($address) {
                        return "`to` = ?";
                    },
                    $addresses
                ))
                . ')',
            $addresses
        )->count();
    if (!$count) continue;
    echo "<tr>";
    // email type information
    echo "<td>";
    echo "<strong>" . Emails::categoryLabel($category) . "</strong>";
    echo "<p><small>" . Emails::categoryDescription($category) . "</small>";
    echo "<br><small>" . $count . " sent to you in the last year</small></p>";
    echo "</td>";
    // don't show unsubscribe options for service categories
    if (Config::get('email.service_categories.' . $category)) {
        echo "<td><em>This category cannot be unsubscribed</em></td>";
        continue;
    }
    // unsubscribe options
    foreach ($addresses as $address) {
        echo "<td>";
        echo new ToggleButton(
            !Emails::isUnsubscribed($address, $category),
            function () use ($address, $category) {
                Emails::resubscribe($address, $category);
            },
            function () use ($address, $category) {
                Emails::unsubscribe($address, $category);
            }
        );
        echo "</td>";
    }
    echo "</tr>";
}
