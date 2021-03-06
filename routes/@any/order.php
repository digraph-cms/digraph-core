<?php
$package->cache_noStore();

$s = $this->helper('strings');
$n = $this->helper('notifications');
$noun = $package->noun();

$form = new Formward\Form($s->string('forms.order.form_title'));

/* sorting mode field */
$options = $cms->config['strings.forms.order.mode.options'];
$manual = $options['manual'];
unset($options['manual']);
$options['manual'] = $manual;
$form['mode'] = new Formward\Fields\Select(
    $s->string('forms.order.mode.title')
);
$form['mode']->required(true, false);
$form['mode']->options($options);
$form['mode']->default($noun['digraph.order.mode']);

/* unsorted field to specify what happens when unspecified children are found on manual sorting */
$form['unsorted'] = new Formward\Fields\Select(
    $s->string('forms.order.unsorted.title')
);
$form['unsorted']->required(true, false);
$options = $cms->config['strings.forms.order.unsorted.options'];
$form['unsorted']->options($options);
$form['unsorted']->default($noun['digraph.order.unsorted'] ?? key($options));

/* field for specifying manual sorting */
$form['manual'] = new Formward\Fields\Ordering(
    $s->string('forms.order.manual.title')
);
$children = [];
foreach ($noun->children() as $c) {
    $children[$c['dso.id']] = $c->name();
}
$form['manual']->opts($children);

/* handle form */
if ($form->handle()) {
    $order = [
        'mode' => $form['mode']->value(),
    ];
    if ($form['mode']->value() == 'manual') {
        $order['unsorted'] = $form['unsorted']->value();
        $order['manual'] = $form['manual']->value();
    }
    $noun['digraph.order'] = $order;
    if ($noun->update()) {
        $n->confirmation(
            $s->string('notifications.confirmation.generic')
        );
    } else {
        $n->error(
            $s->string('notifications.error.generic')
        );
    }
}

echo $form;
?>
<script>
document.addEventListener('DOMContentLoaded', function(e) {
    var mode = document.getElementById('<?php echo $form['mode']->name(); ?>');
    var unsortedWrapper = document.getElementById('_wrapper_<?php echo $form['unsorted']->name(); ?>');
    var manualWrapper = document.getElementById('_wrapper_<?php echo $form['manual']->name(); ?>');
    var changeMode = (e) => {
        if (mode.value == 'manual') {
            unsortedWrapper.classList.remove('hidden');
            manualWrapper.classList.remove('hidden');
        }else {
            unsortedWrapper.classList.add('hidden');
            manualWrapper.classList.add('hidden');
        }
    };
    changeMode();
    mode.addEventListener('change',changeMode);
});
</script>
