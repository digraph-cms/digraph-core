<?php
$noun = $this->package->noun();
echo $this->helper('filters')->filterContentField($noun['digraph.body']);
