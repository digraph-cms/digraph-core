<h1>Authentication settings</h1>
<p>The following login methods are configured for your account.</p>

<h2>Sign-in methods</h2>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\Security\Security;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Users;

Security::requireSecurityCheck();

$user = Users::get(Context::arg('id') ?? Session::user());
if (!$user) {
    throw new HttpError(404, "User not found");
}

$query = DB::query()
    ->from('user_source')
    ->where('user_uuid = ?', [$user->uuid()])
    ->order('created DESC');

$headers = [
    new ColumnHeader('Provider'),
    new ColumnHeader('ID'),
    new ColumnDateFilteringHeader('Added', 'created')
];
$count = $query->count();
if ($count > 1) {
    $headers[] = new ColumnHeader('');
}

$table = new PaginatedTable(
    $query,
    function ($row) use ($count) {
        $source = Users::source($row['source']);
        $tr = [
            $source->providerName($row['provider']) . ' using ' . $source->title(),
            $row['provider_id'],
            Format::date($row['created'])
        ];
        if ($count > 1) {
            $tr[] = new SingleButton(
                'Remove',
                function () use ($source, $row) {
                    DB::query()
                        ->deleteFrom('user_source')
                        ->where(
                            'id = ?',
                            [$row['id']]
                        )
                        ->execute();
                    Notifications::flashConfirmation("Removed authentication method: " . $source->providerName($row['provider']) . ' via ' . $source->title());
                    Context::response()->redirect(Context::url());
                },
                ['warning']
            );
        }
        return $tr;
    },
    $headers
);

echo $table;

echo "<h2>Add login method</h2>";
if ($user->uuid() == $user->uuid()) {
    echo "<ul class='signin-options'>";
    foreach (Users::allSigninURLs(Context::url()) as $k => $url) {
        echo "<li class='signin-source type-" . preg_replace('/_.+$/', '', $k) . " $k'>";
        echo $url->html();
        echo "</li>";
    }
    echo "</ul>";
} else {
    Notifications::printNotice('You cannot add new login methods for other users.');
}
