<h1>Authentication settings</h1>
<p>The following login methods are configured for your account.</p>

<h2>Sign-in methods</h2>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg_string('id', true)) ?? Users::current();
if (!$user)
    throw new HttpError(404);

$query = DB::query()
    ->from('user_source')
    ->where('user_uuid = ?', [$user->uuid()])
    ->order('created DESC');

$headers = [
    new ColumnHeader('Provider'),
    new ColumnHeader('ID'),
    new ColumnDateFilteringHeader('Added', 'created'),
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
            Format::date($row['created']),
        ];
        if ($count > 1) {
            $tr[] = new SingleButton(
                'Remove',
                function () use ($source, $row) {
                    DB::query()
                        ->deleteFrom('user_source')
                        ->where(
                            'id = ?',
                            [$row['id']],
                        )
                        ->execute();
                    Notifications::flashConfirmation("Removed authentication method: " . $source->providerName($row['provider']) . ' via ' . $source->title());
                    Context::response()->redirect(Context::url());
                },
                ['warning'],
            );
        }
        return $tr;
    },
    $headers,
);

echo $table;

if ($user->uuid() == $user->uuid()) {
    echo "<h2>Add login method</h2>";
    Notifications::printNeutralHTML(sprintf(
        'To add a new sign-in method to your account, <a href="%s">sign in here using the method you want to add to your account</a>.',
        Users::signinUrl(Context::url()),
    ));
}
