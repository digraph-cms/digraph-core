<h1>Authentication settings</h1>
<p>Here you can see, add, and remove authentication methods that are configured for your account.</p>

<h2>Sign-in methods</h2>
<?php

use DigraphCMS\Context;
use DigraphCMS\DB\DB;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\ButtonMenus\SingleButton;
use DigraphCMS\UI\DataTables\ColumnHeader;
use DigraphCMS\UI\DataTables\QueryColumnHeader;
use DigraphCMS\UI\DataTables\QueryTable;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\Users\Users;

$query = DB::query()
    ->from('user_source')
    ->where('user_uuid = ?', [Session::user()])
    ->order('created DESC');

$headers = [
    new ColumnHeader('Provider'),
    new ColumnHeader('ID'),
    new QueryColumnHeader('Added', 'created', $query)
];
$count = $query->count();
if ($count > 1) {
    $headers[] = new ColumnHeader('');
}

$table = new QueryTable(
    $query,
    function ($row) use ($count) {
        $source = Users::source($row['source']);
        $tr = [
            $source->providerName($row['provider']) . ' via ' . $source->title(),
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
                            'user_uuid = ? AND source = ? AND provider = ?',
                            [Session::user(), $source->name(), $row['provider']]
                        )
                        ->execute();
                    Notifications::flashConfirmation("Removed " . $source->providerName($row['provider']) . ' via ' . $source->title());
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

echo "<h2>Add sign-in method</h2>";
echo "<ul class='signin-options'>";
foreach (Users::allSigninURLs(Context::url()) as $k => $url) {
    echo "<li class='signin-source type-" . preg_replace('/_.+$/', '', $k) . " $k'>";
    echo $url->html();
    echo "</li>";
}
echo "</ul>";
