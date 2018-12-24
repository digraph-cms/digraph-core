<?php
/* handlers can specify that they shouldn't be cached */
$package->noCache();

/* template fields can be set in $package['fields']; */
$package['fields.page_title'] = 'Example verb handler';

/* you can get a copy of the current noun from the package */
$noun = $package->noun();

?>

<p>
    This route handles requests for the verb "example" on any proper noun.
    The current noun has the ID <code><?php echo $noun['dso.id']; ?></code>.
</p>
