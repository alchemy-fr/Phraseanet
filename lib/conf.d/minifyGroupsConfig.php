<?php

/**
 * Groups configuration for default Minify implementation
 * @package Minify
 */
/**
 * You may wish to use the Minify URI Builder app to suggest
 * changes. http://yourdomain/min/builder/
 * */
$groups = [
    'authentication' => [
        '//assets/modernizr/modernizr.js',
        '//assets/requirejs/require.js',
        '//scripts/apps/login/home/config.js'
    ],
    'modalBox' => [
         '//assets/jquery.ui/i18n/jquery-ui-i18n.js'
    ]
];

return $groups;
