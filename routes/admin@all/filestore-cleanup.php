<?php
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;

$fs = $this->helper('filestore');
$n = $this->helper('notifications');
$s = $this->helper('strings');

$deleted = $fs->cleanup();
$size = array_reduce(
    $deleted,
    function ($c, $i) {
        return $c+$i['size'];
    },
    0
);
$count = count($deleted);
$sizeHR = $s->filesizeHTML($size);

// notification if there is nothing to clean up
if (!$count) {
    $n->notice(
        $s->string('filestore-cleanup.none')
    );
    return;
}

//display list and confirmation button
$form = new \Formward\Form($s->string('filestore-cleanup.title', ['count'=>$count,'size'=>$sizeHR]));

//submit button
$form->submitButton()->label($s->string('forms.confirm_button'));

$form['text'] = new \Formward\Fields\DisplayOnly('');
$form['text']->content(array_reduce(
    $deleted,
    function ($c, $i) use ($s) {
        $content = '<div class="filestore-metacard">';
        $content .= $s->string(
            'filestore-cleanup.cardcontent',
            [
                'size' => $s->filesizeHTML($i['size']),
                'names' => implode(', ', $i['names']),
                'download' => $this->url('admin', 'filestore-cleanup-download', ['f'=>$i['hash']])
            ]
        );
        $content .= '</div>';
        return $c.PHP_EOL.$content;
    }
));

echo $form;
