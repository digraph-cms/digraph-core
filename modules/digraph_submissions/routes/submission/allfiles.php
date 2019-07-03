<p>
    The following is a record of every file uploaded to this submission, at any stage of its creation or any subsequent editing.
    They should be ordered roughly by the date they were added.
</p>
<?php
foreach ($cms->helper('filestore')->allFiles($package->noun()) as $file) {
    echo $file->metaCard();
}