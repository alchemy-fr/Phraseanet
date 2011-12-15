<?php

require_once __DIR__ . "/../../lib/bootstrap.php";
bootstrap::register_autoloads();

$app = require __DIR__ . '/../../lib/Alchemy/Phrasea/Application/Lightbox.php';

$app->run();
