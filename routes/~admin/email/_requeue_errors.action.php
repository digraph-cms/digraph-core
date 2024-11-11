<h1>Re-queue emails</h1>
<p>
    This tool allows you to re-queue emails that failed to send, beginning at a particular date/time.
    This is useful if there was a problem with the email server/config, and you want to re-attempt to send emails that failed while it was broken.
</p>
<?php

use DigraphCMS\Email\Email;
use DigraphCMS\Email\Emails;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\Fields\DatetimeField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\UI\Format;
use DigraphCMS\UI\Notifications;
use DigraphCMS\UI\Pagination\ColumnDateFilteringHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;
use DigraphCMS\URL\URL;

$messages = Emails::select()
    ->where('error is not null')
    ->order('time desc');

if (!$messages->count()) {
    Notifications::printNotice('No emails with errors found');
    return;
}

$form = new FormWrapper();
$form->button()->setText('Preview messages');

$date = (new DatetimeField('Date/time to start re-queue from'))
    ->setRequired(true)
    ->addForm($form);

if ($date->value(false)) {
    $time = $date->value();
    assert($time instanceof DateTime);
    $messages->where('time >= ?', $time->getTimestamp());
    $table = new PaginatedTable(
        $messages,
        function (Email $email) {
            return [
                Format::date($email->time()),
                $email->error(),
                sprintf(
                    "<a href='%s'>%s</a>",
                    $email->url_adminInfo(),
                    $email->subject()
                ),
                $email->to()
            ];
        },
        [
            new ColumnDateFilteringHeader('Date', 'time'),
            new ColumnStringFilteringHeader('Error', 'error'),
            new ColumnStringFilteringHeader('Subject', 'subject'),
            new ColumnStringFilteringHeader('To', '`to`'),
        ]
    );
    $form->addChild($table);
    $confirm = (new CheckboxField(sprintf('Confirm re-queueing these %s messages', $messages->count())))
        ->setRequired(true)
        ->addForm($form);
}

if ($form->ready()) {
    foreach ($messages as $message) {
        Emails::requeue($email);
    }
    Notifications::flashConfirmation(sprintf('Re-queued %s messages', $messages->count()));
    throw new RedirectException(new URL('queued_emails.html'));
}

echo $form;
