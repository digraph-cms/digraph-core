<?php

use DigraphCMS\Context;
use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\INPUT;
use DigraphCMS\HTTP\HttpError;
use DigraphCMS\HTTP\RefreshException;
use DigraphCMS\Session\Session;
use DigraphCMS\UI\CallbackLink;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\UI\Toolbars\ToolbarLink;
use DigraphCMS\Users\User;
use DigraphCMS\Users\Users;

$user = Users::get(Context::arg('id') ?? Session::user());
if (!$user) throw new HttpError(404, "User not found");

echo "<h1>Manage email addresses</h1>";

echo "<div class='navigation-frame navigation-frame--stateless' id='email-management-frame' data-target='frame'>";

echo new PaginatedTable(
    array_reverse($user['emails'] ?? []),
    function (array $row) use ($user): array {
        $i = null;
        foreach ($user['emails'] as $k => $r) {
            if ($r == $row) {
                $i = $k;
                break;
            }
        }
        if ($i === null) return [];
        return [
            $row['address']
                . ($row['comment'] ? '<div><small>' . $row['comment'] . '</small></div>' : ''),
            statusCell($user, $i, $row),
            controlsCell($user, $i, $row)
        ];
    },
    [
        new ColumnHeader('Email'),
        new ColumnHeader('Status'),
        new ColumnHeader('')
    ]
);

$address = (new Field('Add email', (new INPUT)->setAttribute('type', 'email')))
    ->setRequired(true)
    ->addTip('New emails must be verified before they can be used, a confirmation link will be sent to the email you enter.')
    ->addValidator(function (INPUT $input) use ($user) {
        foreach ($user['emails'] as $e) {
            if ($e['address'] == strtolower($input->value())) {
                return "This email is already associated with your account";
            }
        }
        if (!filter_var($input->value(), FILTER_VALIDATE_EMAIL)) {
            return "Please enter a valid email address";
        }
        return null;
    });

$form = (new FormWrapper)
    ->addChild($address)
    ->addCallback(function () use ($user, $address) {
        $user->addEmail($address->value(), 'Manually added from web interface');
        throw new RefreshException();
    });

echo $form;

echo "</div>";

/**
 * @param User $user
 * @param integer $i
 * @param array<string,mixed> $row
 * @return string
 */
function statusCell(User $user, int $i, array $row): string
{
    $out = '';
    if (@$row['verification']) {
        $out .= "<div class='text-cue--warning'>Unverified</div>";
        $out .= "<div><small>Check your inbox for an email verification link</small></div>";
        $out .= "<div><small>Last sent " . Format::datetime($row['verification']['time']) . "</small></div>";
        if (time() > $row['verification']['time'] + 600) {
            $out .= '<div><small>';
            $out .= (new CallbackLink(function () use ($row, $user) {
                $user->sendVerificationEmail($row['address']);
                Notifications::flashConfirmation('Verification email sent');
            }))
                ->setID(md5($row['address']))
                ->setFrameTarget('frame')
                ->addChild('Resend verification email');
            $out .= '</small></div>';
        }
    } elseif (@$row['primary']) {
        $out .= "<div class='text-cue--safe'>Primary, Verified</div>";
    } else {
        $out .= "<div class='text-cue--safe'>Verified</div>";
    }
    return $out;
}

/**
 * @param User $user
 * @param integer $i
 * @param array<string,mixed> $row
 * @return string
 */
function controlsCell(User $user, int $i, array $row): string
{
    $controls = '';
    // make primary button for non-primary verified emails
    if (!@$row['primary'] && !@$row['verification']) {
        $controls .= new ToolbarLink('Make primary', 'star', function () use ($user, $row) {
            $user->setPrimaryEmail($row['address']);
            $user->update();
            Notifications::flashConfirmation('Primary email set: ' . $row['address']);
        });
    }
    // delete button for unverified and when address isn't the last verified one or the primary
    if (!@$row['primary'] && (@$row['verification'] || count($user->emails()) > 1)) {
        $controls .= new ToolbarLink('Delete', 'delete', function () use ($user, $i, $row) {
            unset($user["emails.$i"]);
            $user->update();
            Notifications::flashConfirmation('Email deleted: ' . $row['address']);
        });
    }
    return $controls;
}
