<?php

use DigraphCMS\Config;
use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\ToggleButton;
use DigraphCMS\Users\Permissions;
use DigraphCMS\Users\Users;

/** @var array email addresses to be managed with this form */
$addresses = [];

// only allow access with valid email ID or by being signed in
if (Context::arg('msg')) {
    $email = Emails::get(Context::arg('msg'));
    if (!$email) throw new HttpError(404);
    if ($user = $email->toUser()) {
        $addresses = $user->emails();
    }
    $addresses[] = $email->to();
} else {
    Permissions::requireGroup('users');
    $addresses = Users::current()->emails();
}

// print page title
echo "<h1>Email preferences</h1>";

// print list of addresses being managed
$addresses = array_unique($addresses);
if (!$addresses) {
    Notifications::printError('No email addresses found. You may not have any email addresses associated with your account.');
    return;
}

// display form
$categories = array_keys(Config::get('email.categories'));
$categories = array_unique(array_merge(
    array_keys(Config::get('email.categories')),
    array_map(
        function ($row) {
            return $row['category'];
        },
        DB::query()->from('email_log')
            ->select('DISTINCT category', true)
            ->fetchAll(0)
    )
));

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
        echo "<td><em>Necessary service emails<br>Cannot be unsubscribed</em></td>";
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
