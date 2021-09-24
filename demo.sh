#!/usr/bin/env bash

./vendor/bin/phinx migrate
php -S localhost:8000 -t demo demo/index.php
