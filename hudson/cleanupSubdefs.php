<?php

require __DIR__ . '/../lib/autoload.php';

$app = require __DIR__ . '/../lib/Alchemy/Phrasea/Application/Root.php';

foreach ($app['phraseanet.appbox']->get_databoxes() as $databox) {
    $structure = $databox->get_subdef_structure();
    foreach ($structure as $group => $subdefs) {
        foreach ($subdefs as $subdef) {
            if (!in_array(strtolower($subdef->get_name()), array('preview', 'thumbnail'))) {
                $structure->delete_subdef($group, $subdef->get_name());
            }
        }
    }
}
