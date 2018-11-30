<?php
$package['response.cacheable'] = false;
$package['response.ttl'] = 0;

$fs = $this->helper('filestore');
$n = $this->helper('notifications');
$s = $this->helper('strings');

//set up form
$form = new \Formward\Form($s->string('filestore-cleanup.title', ['count'=>$count,'size'=>$sizeHR]));
if ($form->handle()) {
    $deleted = $fs->cleanupRun();
    $count = 0;
    $errors = 0;
    $size = array_reduce(
        $deleted,
        function ($c, $i) use ($s,$n,&$count,&$errors) {
            //check for errors
            if (!$i['deleted']) {
                $n->warning(
                    $s->string('filestore-cleanup.file-error', $i)
                );
                $errors++;
                return $c;
            }
            //return reduce operation
            $count++;
            return $c+$i['size'];
        },
        0
    );
    $sizeHR = $s->filesizeHTML($size);
    if ($count) {
        $n->confirmation(
            $s->string('filestore-cleanup.confirmation', [
                'count' => $count,
                'size' => $sizeHR
            ])
        );
    }
    if ($errors) {
        $n->error(
            $s->string('filestore-cleanup.error', [
                'errors' => $errors
            ])
        );
    }
    return;
}

//load current deleted state
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
                'mtime' => $s->dateHTML($i['mtime']),
                'download' => $this->url('admin', 'filestore-cleanup-download', ['f'=>$i['hash']])
            ]
        );
        $content .= '</div>';
        return $c.PHP_EOL.$content;
    }
));

echo $form;
