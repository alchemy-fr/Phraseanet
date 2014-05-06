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
        $youtube_callback = $app->url('prod_bridge_callback', array('api_name' => 'youtube'));
    } catch (RouteNotFoundException $e) {
        $youtube_callback = null;
    }

    try {
        $flickr_callback = $app->url('prod_bridge_callback', array('api_name' => 'flickr'));
    } catch (RouteNotFoundException $e) {
        $flickr_callback = null;
    }

    try {
        $dailymotion_callback = $app->url('prod_bridge_callback', array('api_name' => 'dailymotion'));
    } catch (RouteNotFoundException $e) {
        $dailymotion_callback = null;
    }

    $recaptchaDoc = '<a href="http://www.google.com/recaptcha">http://www.google.com/recaptcha</a>';
    $imagineDoc = '<a href="http://imagine.readthedocs.org/en/latest/usage/introduction.html">http://imagine.readthedocs.org/en/latest/usage/introduction.html</a>';

    return array(
        array(
            'section' => _('HTTP Server'),
            'vars'    => array(
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_default_lng',
                    'comment'   => _('Default language'),
                    'default'   => 'fr_FR',
                    'available' => $avLanguages,
                    'required'  => true
                ),
                array(
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_STATIC_URL',
                    'end_slash' => false,
                    'comment'   => _('Static URL'),
                    'help'      => _('optional'),
                    'default'   => '',
                )
            )
        ), array(
            'section' => _('Maintenance state'),
            'rolled'  => true,
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_TEXT,
                    'name'    => 'GV_message',
                    'comment' => _('Maintenance message'),
                    'default' => "May the force be with you"
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_message_on',
                    'comment' => _('Enable maintenance message broadcast'),
                    'default' => false
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_log_errors',
                    'comment' => _('Log errors'),
                    'default' => false
                )
            )
        ), array(
            'section' => _('Webservices connectivity'),
            'vars'    => array(
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_google_api',
                    'comment'  => _('Use Google API'),
                    'default'  => true,
                    'required' => true
                ),
                array(
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_i18n_service',
                    'comment'   => _('Geonames server address'),
                    'default'   => 'https://geonames.alchemyasp.com/',
                    'end_slash' => true,
                    'required'  => true
                ),
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_captchas',
                    'comment'  => _('Use recaptcha API'),
                    'help'     => _(sprintf('See documentation at %s', $recaptchaDoc)),
                    'default'  => false,
                    'required' => true
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_captcha_public_key',
                    'comment' => _('Recaptcha public key'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_captcha_private_key',
                    'comment' => _('Recaptcha private key'),
                    'default' => ''
                )
            )
        ), array(
            'section' => _('Youtube connectivity'),
            'vars'    => array(
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_youtube_api',
                    'comment'  => _('Use youtube API'),
                    'help'     => sprintf(_('Create API account at %s, then use %s as callback URL value'), $youtube_console_url, $youtube_callback),
                    'default'  => false,
                    'required' => true
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_youtube_client_id',
                    'comment' => _('Youtube public key'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_youtube_client_secret',
                    'comment' => _('Youtube secret key'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_youtube_dev_key',
                    'comment' => _('Youtube developer key'),
                    'help'    => sprintf(_('See %s'), $dashboard_youtube),
                    'default' => ''
                )
            )
        ), array(
            'section' => _('FlickR connectivity'),
            'vars'    => array(
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_flickr_api',
                    'comment'  => _('Use Flickr API'),
                    'help'     => sprintf(_('Create API account at %s, then use %s as callback URL value'), $create_api_flickr, $flickr_callback),
                    'default'  => false,
                    'required' => true
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_flickr_client_id',
                    'comment' => _('Flickr public key'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_flickr_client_secret',
                    'comment' => _('Flickr secret key'),
                    'default' => ''
                )
            )
        ), array(
            'section' => _('Dailymotion connectivity'),
            'vars'    => array(
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_dailymotion_api',
                    'comment'  => _('Use Dailymotion API'),
                    'help'     => sprintf(_('Create API account at %s, then use %s as callback URL value'), $create_api_dailymotion, $dailymotion_callback),
                    'default'  => false,
                    'required' => true
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_dailymotion_client_id',
                    'comment' => _('Dailymotion public key'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_dailymotion_client_secret',
                    'comment' => _('Dailymotion secret key'),
                    'default' => ''
                )
            )
        ), array(
            'section' => _('Phraseanet client API'),
            'vars'    => array(
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_client_navigator',
                    'comment'  => _('Authorize *Phraseanet Navigator*'),
                    'help'     => _('*Phraseanet Navigator* is a smartphone application that allow user to connect on this instance'),
                    'default'  => true,
                    'required' => true
                ),
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_client_officeplugin',
                    'comment'  => _('Authorize Microsoft Office Plugin to connect.'),
                    'default'  => true,
                    'required' => true
                ),
            )
        ), array(
            'section' => _('Documents storage'),
            'vars'    => array(
                array(
                    'type'      => \registry::TYPE_STRING,
                    'name'      => 'GV_base_datapath_noweb',
                    'end_slash' => true,
                    'comment'   => _('Default path for datas'),
                    'default'   => '',
                    'required'  => true
                )
            )
        ), array(
            'section' => _('Executables settings'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_PHP_INI',
                    'comment' => _('php.ini path'),
                    'help'    => _('Empty if not used'),
                    'default' => ''
                ),
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_imagine_driver',
                    'default'   => '',
                    'comment'   => _('Imagine driver'),
                    'help'      => _(sprintf('See documentation at %s', $imagineDoc)),
                    'available' => array(
                        ''        => 'Auto',
                        'gmagick' => 'GraphicsMagick',
                        'imagick' => 'ImageMagick',
                        'gd'      => 'GD',
                    )
                ),
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ffmpeg_threads',
                    'comment' => _('Number of threads to use for FFMpeg'),
                    'default' => 2
                ),
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_pdfmaxpages',
                    'comment' => _('Maximum number of pages to be extracted from PDF'),
                    'default' => 5
                )
            )
        ), array(
            'section' => _('Main configuration'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_force_push_authentication',
                    'comment' => _('Enable Forcing authentication to see push content'),
                    'help'      => _('Adds an option to the push form submission to restrict push recipient(s) to Phraseanet users only.'),
                    'default' => false
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_adminMail',
                    'comment' => _('Admin email'),
                    'default' => 'support@alchemy.fr'
                ),
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_view_bas_and_coll',
                    'comment'  => _('Display the name of databases and collections'),
                    'default'  => true,
                    'required' => true
                ),
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_choose_export_title',
                    'comment'  => _('Choose the title of the document to export'),
                    'default'  => false,
                    'required' => true
                ),
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_default_export_title',
                    'comment'   => _('Default export title'),
                    'default'   => 'title',
                    'available' => array(
                        'title' => _('Document title'),
                        'original' => _('Original name'),
                    )
                ),
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_social_tools',
                    'comment'   => _('Enable this setting to share on Facebook and Twitter'),
                    'available' => array(
                        'none' => _('Disabled'),
                        'publishers' => _('Publishers'),
                        'all' => _('Enabled'),
                    ),
                    'default'    => 'none',
                    'required'   => false
                )
            )
        ), array(
            'section' => _('Homepage'),
            'rolled'  => true,
            'vars'    => array(
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_home_publi',
                    'comment'   => _('Homepage slideshow'),
                    'default'   => 'COOLIRIS',
                    'available' => array(
                        'DISPLAYx1' => _('Single image'),
                        'SCROLL'    => _('Slide show'),
                        'COOLIRIS'  => 'Cooliris',
                        'CAROUSEL'  => _('Carousel'),
                        'GALLERIA'  => _('Gallery')
                    ),
                    'required'  => true
                )
            )
        ), array(
            'section' => _('Search engine'),
            'rolled'  => true,
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_min_letters_truncation',
                    'comment' => _('Minimum number of letters before truncation'),
                    'help'    => _('Used in search engine'),
                    'default' => 1
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_defaultQuery',
                    'comment' => _('Default query'),
                    'default' => 'all'
                ),
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_defaultQuery_type',
                    'comment'   => _('Default searched type'),
                    'help'      => _('Used when opening the application'),
                    'available' => array(
                        '0' => _('Documents'),
                        '1' => _('Stories'),
                    ),
                    'default' => '0'
                )
            )
        ), array(
            'section' => _('Report'),
            'rolled'  => true,
            'vars'    => array(
                array(
                    'type'     => \registry::TYPE_BOOLEAN,
                    'name'     => 'GV_anonymousReport',
                    'comment'  => _('Anonymous report'),
                    'help'     => _('Hide information about users'),
                    'default'  => false,
                    'required' => true
                )
            )
        ), array(
            'section' => _('Additionnal modules'),
            'rolled'  => true,
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_thesaurus',
                    'comment' => _('Enable thesaurus'),
                    'default' => true
                ), array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_multiAndReport',
                    'comment' => _('Enable multi-doc mode'),
                    'default' => true
                ), array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_seeOngChgDoc',
                    'comment' => _('Enable HD substitution'),
                    'default' => false
                ), array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_seeNewThumb',
                    'comment' => _('Enable thumbnail substitution'),
                    'default' => false
                )
            )
        )
        , array(
            'section' => _('Emails'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_defaulmailsenderaddr',
                    'comment' => _('Default mail sender address'),
                    'default' => 'phraseanet@example.com'
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_email_prefix',
                    'comment' => _('Prefix for notification emails'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_smtp',
                    'comment' => _('Use a SMTP server'),
                    'default' => false
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_smtp_auth',
                    'comment' => _('Enable SMTP authentication'),
                    'default' => false
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_smtp_host',
                    'comment' => _('SMTP host'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_smtp_port',
                    'comment' => _('SMTP port'),
                    'default' => ''
                ),
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_smtp_secure',
                    'comment'   => _('SMTP encryption'),
                    'default'   => 'tls',
                    'available' => array(
                        'none' => _('None'),
                        'ssl' => 'SSL',
                        'tls' => 'TLS'
                    ),
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_smtp_user',
                    'comment' => _('SMTP user'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_PASSWORD,
                    'name'    => 'GV_smtp_password',
                    'comment' => _('SMTP password'),
                    'default' => ''
                )
            )
        ), array(
            'section' => _('FTP Export'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_activeFTP',
                    'comment' => _('Enable FTP export'),
                    'help'    => _('Available in multi-export tab'),
                    'default' => false
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_ftp_for_user',
                    'comment' => _('Enable FTP for users'),
                    'help'    => _('By default it is available for admins'),
                    'default' => false
                )
            )
        ), array(
            'section' => _('Client'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_download_max',
                    'comment' => _('Maximum megabytes allowed for download'),
                    'help'    => _('If request is bigger, then mail is still available'),
                    'default' => 120
                ),
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_search',
                    'comment' => _('Search tab position'),
                    'default' => 1
                ),
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_advsearch',
                    'comment' => _('Advanced search tab position'),
                    'default' => 2
                ),
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_topics',
                    'comment' => _('Topics tab position'),
                    'default' => 0
                ),
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_ong_actif',
                    'comment' => _('Active tab position'),
                    'default' => 1
                ),
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_client_render_topics',
                    'comment'   => _('Topics display mode'),
                    'available' => array(
                        'tree'    => _('Trees'),
                        'popups'  => _('Drop-down'),
                    ),
                    'default' => 'tree'
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_rollover_reg_preview',
                    'comment' => _('Enable roll-over on stories'),
                    'default' => true
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_rollover_chu',
                    'comment' => _('Enable roll-over on basket elements'),
                    'default' => true
                ),
                array(
                    'type'      => \registry::TYPE_ENUM,
                    'name'      => 'GV_client_coll_ckbox',
                    'comment'   => _('Collections display mode'),
                    'default'   => 'checkbox',
                    'available' => array(
                        'popup' => _('Drop-down'),
                        'checkbox' => _('Check-box'),
                    )
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_viewSizeBaket',
                    'comment' => _('Display the total size of the document basket'),
                    'default' => true
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_clientAutoShowProposals',
                    'comment' => _('Display proposals tab'),
                    'default' => true
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_needAuth2DL',
                    'comment' => _('Require authentication to download documents'),
                    'help'    => _('Used for guest account'),
                    'default' => true
                ),
                array(
                    'type'    => 'boolean',
                    'name'    => 'GV_requireTOUValidationForExport',
                    'comment' => _('Users must accept Terms of Use for each export'),
                    'default' => false
                )
            )
        ), array(
            'section' => _('Registration'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_autoselectDB',
                    'comment' => _('Auto select databases'),
                    'help'    => _('This option disables the selecting of the databases on which a user can register himself, and registration is made on all granted databases.'),
                    'default' => true
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_autoregister',
                    'comment' => _('Enable auto registration'),
                    'default' => false
                )
            )
        ), array(
            'section' => _('Push configuration'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_validation_reminder',
                    'comment' => _('Number of days before the end of the validation to send a reminder email'),
                    'default' => 2
                ),
                array(
                    'type'    => \registry::TYPE_INTEGER,
                    'name'    => 'GV_val_expiration',
                    'comment' => _('Default validation links duration'),
                    'help'    => _('If set to 0, duration is permanent'),
                    'default' => 10
                )
            )
        ), array(
            'section' => _('Robot indexing'),
            'vars'    => array(
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_homeTitle',
                    'comment' => _('Application title'),
                    'default' => 'Phraseanet'
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_metaKeywords',
                    'comment' => _('Keywords used for indexing purposes by search engines robots'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_TEXT,
                    'name'    => 'GV_metaDescription',
                    'comment' => _('Application description'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_STRING,
                    'name'    => 'GV_googleAnalytics',
                    'comment' => _('Google Analytics identifier'),
                    'default' => ''
                ),
                array(
                    'type'    => \registry::TYPE_BOOLEAN,
                    'name'    => 'GV_allow_search_engine',
                    'comment' => _('Allow the website to be indexed by search engines like Google'),
                    'default' => true
                ),
            )
        )
    );
}, array($app));
