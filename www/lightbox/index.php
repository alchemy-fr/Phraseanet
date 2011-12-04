<?php

require_once dirname(__FILE__) . "/../../lib/bootstrap.php";
bootstrap::register_autoloads();

$app = require dirname(__FILE__) . '/../../lib/classes/module/Lightbox.php';

$app->run();
