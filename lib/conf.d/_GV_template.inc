<?php

use Alchemy\Phrasea\Application;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

return call_user_func_array(function(Application $app) {

    $avLanguages = $app['locales.available'];

    $youtube_console_url = '<a href="https://code.google.com/apis/console/" target="_blank">https://code.google.com/apis/console/</a>';
    $dashboard_youtube = '<a href="https://code.google.com/apis/youtube/dashboard/" target="_blank">https://code.google.com/apis/youtube/dashboard/</a>';
    $create_api_flickr = '<a href="https://secure.flickr.com/services/apps/create/" target="_blank">https://secure.flickr.com/services/apps/create/</a>';
    $create_api_dailymotion = '<a href="http://www.dailymotion.com/profile/developer" target="_blank">http://www.dailymotion.com/profile/developer</a>';

    try {
        $youtube_callback = $app->url('prod_bridge_callback', ['api_name' => 'youtube']);
    } catch (RouteNotFoundException $e) {
        $youtube_callback = null;
    }

    try {
        $flickr_callback = $app->url('prod_bridge_callback', ['api_name' => 'flickr']);
    } catch (RouteNotFoundException $e) {
        $flickr_callback = null;
    }

    try {
        $dailymotion_callback = $app->url('prod_bridge_callback', ['api_name' => 'dailymotion']);
    } catch (RouteNotFoundException $e) {
        $dailymotion_callback = null;
    }

    $recaptchaDoc = '<a href="http://www.google.com/recaptcha">http://www.google.com/recaptcha</a>';
    $imagineDoc = '<a href="http://imagine.readthedocs.org/en/latest/usage/introduction.html">http://imagine.readthedocs.org/en/latest/usage/introduction.html</a>';

    return [
        [
            'section' => $app->trans('HTTP Server'),
            'vars'    => [
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_default_lng',
                    'comment'   => $app->trans('Default language'),
                    'default'   => 'fr',
                    'available' => $avLanguages,
                    'required'  => true
                ],
                [
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_STATIC_URL',
                    'end_slash' => false,
                    'comment'   => $app->trans('Static URL'),
                    'help'      => $app->trans('optional'),
                    'default'   => '',
                ]
            ]
        ], [
            'section' => $app->trans('Maintenance state'),
            'rolled'  => true,
            'vars'    => [
                [
                    'type'    => \registry::TYPE_TEXT,
                    'name'    => 'GV_message',
                    'comment' => $app->trans('Maintenance message'),
                    'default' => "May the force be with you"
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_message_on',
                    'comment' => $app->trans('Enable maintenance message broadcast'),
                    'default' => false
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_log_errors',
                    'comment' => $app->trans('Log errors'),
                    'default' => false
                ]
            ]
        ], [
            'section' => $app->trans('Webservices connectivity'),
            'vars'    => [
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_google_api',
                    'comment'  => $app->trans('Use Google API'),
                    'default'  => true,
                    'required' => true
                ],
                [
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_i18n_service',
                    'comment'   => $app->trans('Geonames server address'),
                    'default'   => 'https://geonames.alchemyasp.com/',
                    'end_slash' => true,
                    'required'  => true
                ],
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_captchas',
                    'comment'  => $app->trans('Use recaptcha API'),
                    'help'     => $app->trans('See documentation at %website%', ['%website%' => $recaptchaDoc]),
                    'default'  => false,
                    'required' => true
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_captcha_public_key',
                    'comment' => $app->trans('Recaptcha public key'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_captcha_private_key',
                    'comment' => $app->trans('Recaptcha private key'),
                    'default' => ''
                ]
            ]
        ], [
            'section' => $app->trans('Youtube connectivity'),
            'vars'    => [
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_youtube_api',
                    'comment'  => $app->trans('Use youtube API'),
                    'help'     => $app->trans('Create API account at %website_url%, then use %callback_url% as callback URL value', ['%website_url%' => $youtube_console_url, '%callback_url%' => $youtube_callback]),
                    'default'  => false,
                    'required' => true
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_youtube_client_id',
                    'comment' => $app->trans('Youtube public key'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_youtube_client_secret',
                    'comment' => $app->trans('Youtube secret key'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_youtube_dev_key',
                    'comment' => $app->trans('Youtube developer key'),
                    'help'    => $app->trans('See %url%', ['%url%' => $dashboard_youtube]),
                    'default' => ''
                ]
            ]
        ], [
            'section' => $app->trans('FlickR connectivity'),
            'vars'    => [
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_flickr_api',
                    'comment'  => $app->trans('Use Flickr API'),
                    'help'     => $app->trans('Create API account at %website_url%, then use %callback_url% as callback URL value', ['%website_url%' => $create_api_flickr, '%callback_url%' => $flickr_callback]),
                    'default'  => false,
                    'required' => true
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_flickr_client_id',
                    'comment' => $app->trans('Flickr public key'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_flickr_client_secret',
                    'comment' => $app->trans('Flickr secret key'),
                    'default' => ''
                ]
            ]
        ], [
            'section' => $app->trans('Dailymotion connectivity'),
            'vars'    => [
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_dailymotion_api',
                    'comment'  => $app->trans('Use Dailymotion API'),
                    'help'     => $app->trans('Create API account at %website_url%, then use %callback_url% as callback URL value', ['%website_url%' => $create_api_dailymotion, '%callback_url%' => $dailymotion_callback]),
                    'default'  => false,
                    'required' => true
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_dailymotion_client_id',
                    'comment' => $app->trans('Dailymotion public key'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_dailymotion_client_secret',
                    'comment' => $app->trans('Dailymotion secret key'),
                    'default' => ''
                ]
            ]
        ], [
            'section' => $app->trans('Phraseanet client API'),
            'vars'    => [
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_client_navigator',
                    'comment'  => $app->trans('Authorize *Phraseanet Navigator*'),
                    'help'     => $app->trans('*Phraseanet Navigator* is a smartphone application that allow user to connect on this instance'),
                    'default'  => true,
                    'required' => true
                ],
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_client_officeplugin',
                    'comment'  => $app->trans('Authorize Microsoft Office Plugin to connect.'),
                    'default'  => true,
                    'required' => true
                ],
            ]
        ], [
            'section' => $app->trans('Documents storage'),
            'vars'    => [
                [
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_base_datapath_noweb',
                    'end_slash' => true,
                    'comment'   => $app->trans('Default path for datas'),
                    'default'   => '',
                    'required'  => true
                ]
            ]
        ], [
            'section' => $app->trans('Executables settings'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_h264_streaming',
                    'comment' => $app->trans('Enable H264 stream mode'),
                    'help'    => $app->trans('Use with mod_token. Attention requires the apache modules and mod_h264_streaming mod_auth_token'),
                    'default' => false
                ],
                [
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_mod_auth_token_directory',
                    'end_slash' => true,
                    'comment'   => $app->trans('Auth_token mount point'),
                    'default'   => false
                ],
                [
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_mod_auth_token_directory_path',
                    'end_slash' => false,
                    'comment'   => $app->trans('Auth_token directory path'),
                    'default'   => false
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_mod_auth_token_passphrase',
                    'comment' => $app->trans('Auth_token passphrase'),
                    'help'    => $app->trans('Defined in Apache configuration'),
                    'default' => false
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_PHP_INI',
                    'comment' => $app->trans('php.ini path'),
                    'help'    => $app->trans('Empty if not used'),
                    'default' => ''
                ],
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_imagine_driver',
                    'default'   => '',
                    'comment'   => $app->trans('Imagine driver'),
                    'help'      => $app->trans('See documentation at %website%', ['%website%' => $imagineDoc]),
                    'available' => [
                        ''        => 'Auto',
                        'gmagick' => 'GraphicsMagick',
                        'imagick' => 'ImageMagick',
                        'gd'      => 'GD',
                    ]
                ],
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ffmpeg_threads',
                    'comment' => $app->trans('Number of threads to use for FFMpeg'),
                    'default' => 2
                ],
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_pdfmaxpages',
                    'comment' => $app->trans('Maximum number of pages to be extracted from PDF'),
                    'default' => 5
                ]
            ]
        ], [
            'section' => $app->trans('Main configuration'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_adminMail',
                    'comment' => $app->trans('Admin email'),
                    'default' => 'support@alchemy.fr'
                ],
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_view_bas_and_coll',
                    'comment'  => $app->trans('Display the name of databases and collections'),
                    'default'  => true,
                    'required' => true
                ],
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_choose_export_title',
                    'comment'  => $app->trans('Choose the title of the document to export'),
                    'default'  => false,
                    'required' => true
                ],
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_default_export_title',
                    'comment'   => $app->trans('Default export title'),
                    'default'   => 'title',
                    'available' => [
                        'title' => $app->trans('Document title'),
                        'original' => $app->trans('Original name'),
                    ]
                ],
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_social_tools',
                    'comment'   => $app->trans('Enable this setting to share on Facebook and Twitter'),
                    'available' => [
                        'none' => $app->trans('Disabled'),
                        'publishers' => $app->trans('Publishers'),
                        'all' => $app->trans('Enabled'),
                    ],
                    'default'    => 'none',
                    'required'   => false
                ]
            ]
        ], [
            'section' => $app->trans('Homepage'),
            'rolled'  => true,
            'vars'    => [
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_home_publi',
                    'comment'   => $app->trans('Homepage slideshow'),
                    'default'   => 'COOLIRIS',
                    'available' => [
                        'DISPLAYx1' => $app->trans('Single image'),
                        'SCROLL'    => $app->trans('Slide show'),
                        'COOLIRIS'  => 'Cooliris',
                        'CAROUSEL'  => $app->trans('Carousel'),
                        'GALLERIA'  => $app->trans('Gallery')
                    ],
                    'required'  => true
                ]
            ]
        ], [
            'section' => $app->trans('Search engine'),
            'rolled'  => true,
            'vars'    => [
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_min_letters_truncation',
                    'comment' => $app->trans('Minimum number of letters before truncation'),
                    'help'    => $app->trans('Used in search engine'),
                    'default' => 1
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_defaultQuery',
                    'comment' => $app->trans('Default query'),
                    'default' => 'all'
                ],
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_defaultQuery_type',
                    'comment'   => $app->trans('Default searched type'),
                    'help'      => $app->trans('Used when opening the application'),
                    'available' => [
                        '0' => $app->trans('Documents'),
                        '1' => $app->trans('Stories'),
                    ],
                    'default' => '0'
                ]
            ]
        ], [
            'section' => $app->trans('Report'),
            'rolled'  => true,
            'vars'    => [
                [
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_anonymousReport',
                    'comment'  => $app->trans('Anonymous report'),
                    'help'     => $app->trans('Hide information about users'),
                    'default'  => false,
                    'required' => true
                ]
            ]
        ], [
            'section' => $app->trans('Additionnal modules'),
            'rolled'  => true,
            'vars'    => [
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_thesaurus',
                    'comment' => $app->trans('Enable thesaurus'),
                    'default' => true
                ], [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_multiAndReport',
                    'comment' => $app->trans('Enable multi-doc mode'),
                    'default' => true
                ], [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_seeOngChgDoc',
                    'comment' => $app->trans('Enable HD substitution'),
                    'default' => false
                ], [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_seeNewThumb',
                    'comment' => $app->trans('Enable thumbnail substitution'),
                    'default' => false
                ]
            ]
        ]
        , [
            'section' => $app->trans('Emails'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_defaulmailsenderaddr',
                    'comment' => $app->trans('Default mail sender address'),
                    'default' => 'phraseanet@example.com'
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_email_prefix',
                    'comment' => $app->trans('Prefix for notification emails'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_smtp',
                    'comment' => $app->trans('Use a SMTP server'),
                    'default' => false
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_smtp_auth',
                    'comment' => $app->trans('Enable SMTP authentication'),
                    'default' => false
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_smtp_host',
                    'comment' => $app->trans('SMTP host'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_smtp_port',
                    'comment' => $app->trans('SMTP port'),
                    'default' => ''
                ],
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_smtp_secure',
                    'comment'   => $app->trans('SMTP encryption'),
                    'default'   => 'tls',
                    'available' => [
                        'none' => $app->trans('None'),
                        'ssl' => 'SSL',
                        'tls' => 'TLS'
                    ],
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_smtp_user',
                    'comment' => $app->trans('SMTP user'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_smtp_password',
                    'comment' => $app->trans('SMTP password'),
                    'default' => ''
                ]
            ]
        ], [
            'section' => $app->trans('FTP Export'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_activeFTP',
                    'comment' => $app->trans('Enable FTP export'),
                    'help'    => $app->trans('Available in multi-export tab'),
                    'default' => false
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_ftp_for_user',
                    'comment' => $app->trans('Enable FTP for users'),
                    'help'    => $app->trans('By default it is available for admins'),
                    'default' => false
                ]
            ]
        ], [
            'section' => $app->trans('Client'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_download_max',
                    'comment' => $app->trans('Maximum megabytes allowed for download'),
                    'help'    => $app->trans('If request is bigger, then mail is still available'),
                    'default' => 120
                ],
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_search',
                    'comment' => $app->trans('Search tab position'),
                    'default' => 1
                ],
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_advsearch',
                    'comment' => $app->trans('Advanced search tab position'),
                    'default' => 2
                ],
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_topics',
                    'comment' => $app->trans('Topics tab position'),
                    'default' => 0
                ],
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_actif',
                    'comment' => $app->trans('Active tab position'),
                    'default' => 1
                ],
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_client_render_topics',
                    'comment'   => $app->trans('Topics display mode'),
                    'available' => [
                        'tree'    => $app->trans('Trees'),
                        'popups'  => $app->trans('Drop-down'),
                    ],
                    'default' => 'tree'
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_rollover_reg_preview',
                    'comment' => $app->trans('Enable roll-over on stories'),
                    'default' => true
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_rollover_chu',
                    'comment' => $app->trans('Enable roll-over on basket elements'),
                    'default' => true
                ],
                [
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_client_coll_ckbox',
                    'comment'   => $app->trans('Collections display mode'),
                    'default'   => 'checkbox',
                    'available' => [
                        'popup' => $app->trans('Drop-down'),
                        'checkbox' => $app->trans('Check-box'),
                    ]
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_viewSizeBaket',
                    'comment' => $app->trans('Display the total size of the document basket'),
                    'default' => true
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_clientAutoShowProposals',
                    'comment' => $app->trans('Display proposals tab'),
                    'default' => true
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_needAuth2DL',
                    'comment' => $app->trans('Require authentication to download documents'),
                    'help'    => $app->trans('Used for guest account'),
                    'default' => true
                ],
                [
                    'type'    => 'boolean',
                    'name'    => 'GV_requireTOUValidationForExport',
                    'comment' => $app->trans('Users must accept Terms of Use for each export'),
                    'default' => false
                ]
            ]
        ], [
            'section' => $app->trans('Registration'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_autoselectDB',
                    'comment' => $app->trans('Auto select databases'),
                    'help'    => $app->trans('This option disables the selecting of the databases on which a user can register himself, and registration is made on all granted databases.'),
                    'default' => true
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_autoregister',
                    'comment' => $app->trans('Enable auto registration'),
                    'default' => false
                ]
            ]
        ], [
            'section' => $app->trans('Push configuration'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_validation_reminder',
                    'comment' => $app->trans('Number of days before the end of the validation to send a reminder email'),
                    'default' => 2
                ],
                [
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_val_expiration',
                    'comment' => $app->trans('Default validation links duration'),
                    'help'    => $app->trans('If set to 0, duration is permanent'),
                    'default' => 10
                ]
            ]
        ], [
            'section' => $app->trans('Robot indexing'),
            'vars'    => [
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_homeTitle',
                    'comment' => $app->trans('Application title'),
                    'default' => 'Phraseanet'
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_metaKeywords',
                    'comment' => $app->trans('Keywords used for indexing purposes by search engines robots'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_TEXT,
                    'name'    => 'GV_metaDescription',
                    'comment' => $app->trans('Application description'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_googleAnalytics',
                    'comment' => $app->trans('Google Analytics identifier'),
                    'default' => ''
                ],
                [
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_allow_search_engine',
                    'comment' => $app->trans('Allow the website to be indexed by search engines like Google'),
                    'default' => true
                ],
            ]
        ]
    ];
}, [$app]);
