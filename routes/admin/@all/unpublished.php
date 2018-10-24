<?php
$search = $cms->factory()->search();
$search->where('(NOT ${digraph.published})');
$results = $search->execute();

var_dump($results);
