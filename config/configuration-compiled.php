<?php
return [
    'servername' => 'dev.phraseanet.vb',
    'languages' => [
        'available' => [
        ],
        'default' => 'fr',
    ],
    'main' => [
        'maintenance' => false,
        'key' => 'LPel+kDbqDgAKPI2',
        'api_require_ssl' => true,
        'database' => [
            'host' => 'localhost',
            'port' => '3306',
            'user' => 'phraseanet',
            'password' => 'phraseanet',
            'dbname' => 'ab_master',
            'driver' => 'pdo_mysql',
            'charset' => 'UTF8',
        ],
        'database-test' => [
            'driver' => 'pdo_sqlite',
            'path' => '/tmp/db.sqlite',
            'charset' => 'UTF8',
        ],
        'cache' => [
            'type' => 'ArrayCache',
            'options' => [
            ],
        ],
        'search-engine' => [
            'type' => 'elasticsearch',
            'options' => [
                'host' => 'localhost',
                'port' => 9200,
            ],
        ],
        'task-manager' => [
            'status' => 'started',
            'enabled' => true,
            'options' => [
                'protocol' => 'tcp',
                'host' => '127.0.0.1',
                'port' => 6660,
                'linger' => 500,
            ],
            'logger' => [
                'max-files' => 10,
                'enabled' => true,
                'level' => 'INFO',
            ],
        ],
        'session' => [
            'type' => 'file',
            'options' => [
            ],
            'ttl' => 86400,
        ],
        'binaries' => [
            'php_binary' => '/usr/bin/php',
            'pdf2swf_binary' => null,
            'swf_extract_binary' => '/usr/bin/swfextract',
            'swf_render_binary' => '/usr/bin/swfrender',
            'unoconv_binary' => '/usr/bin/unoconv',
            'ffmpeg_binary' => '/usr/local/bin/ffmpeg',
            'ffprobe_binary' => '/usr/local/bin/ffprobe',
            'mp4box_binary' => '/usr/bin/MP4Box',
            'pdftotext_binary' => '/usr/bin/pdftotext',
            'ghostscript_binary' => '/usr/bin/gs',
        ],
        'storage' => [
            'subdefs' => '/vagrant/datas',
            'cache' => '/vagrant/cache',
            'log' => '/vagrant/logs',
            'download' => '/vagrant/tmp/download',
            'lazaret' => '/vagrant/tmp/lazaret',
            'caption' => '/vagrant/tmp/caption',
        ],
        'bridge' => [
            'youtube' => [
                'enabled' => false,
                'client_id' => null,
                'client_secret' => null,
                'developer_key' => null,
            ],
            'flickr' => [
                'enabled' => false,
                'client_id' => null,
                'client_secret' => null,
            ],
            'dailymotion' => [
                'enabled' => false,
                'client_id' => null,
                'client_secret' => null,
            ],
        ],
    ],
    'trusted-proxies' => [
    ],
    'debugger' => [
        'allowed-ips' => [
            '127.0.0.1',
            '10.0.2.15',
            '192.168.56.3',
        ],
    ],
    'border-manager' => [
        'enabled' => true,
        'extension-mapping' => [
        ],
        'checkers' => [
            [
                'type' => 'Checker\Sha256',
                'enabled' => true,
            ],
            [
                'type' => 'Checker\UUID',
                'enabled' => true,
            ],
            [
                'type' => 'Checker\Colorspace',
                'enabled' => false,
                'options' => [
                    'colorspaces' => [
                        'cmyk',
                        'grayscale',
                        'rgb',
                    ],
                ],
            ],
            [
                'type' => 'Checker\Dimension',
                'enabled' => false,
                'options' => [
                    'width' => 80,
                    'height' => 160,
                ],
            ],
            [
                'type' => 'Checker\Extension',
                'enabled' => false,
                'options' => [
                    'extensions' => [
                        'jpg',
                        'jpeg',
                        'bmp',
                        'tif',
                        'gif',
                        'png',
                        'pdf',
                        'doc',
                        'odt',
                        'mpg',
                        'mpeg',
                        'mov',
                        'avi',
                        'xls',
                        'flv',
                        'mp3',
                        'mp2',
                    ],
                ],
            ],
            [
                'type' => 'Checker\Filename',
                'enabled' => false,
                'options' => [
                    'sensitive' => true,
                ],
            ],
            [
                'type' => 'Checker\MediaType',
                'enabled' => false,
                'options' => [
                    'mediatypes' => [
                        'Audio',
                        'Document',
                        'Flash',
                        'Image',
                        'Video',
                    ],
                ],
            ],
        ],
    ],
    'authentication' => [
        'auto-create' => [
            'templates' => [
            ],
        ],
        'captcha' => [
            'enabled' => true,
            'trials-before-display' => 9,
        ],
        'providers' => [
            'facebook' => [
                'enabled' => false,
                'options' => [
                    'app-id' => '',
                    'secret' => '',
                ],
            ],
            'twitter' => [
                'enabled' => false,
                'options' => [
                    'consumer-key' => '',
                    'consumer-secret' => '',
                ],
            ],
            'google-plus' => [
                'enabled' => false,
                'options' => [
                    'client-id' => '',
                    'client-secret' => '',
                ],
            ],
            'github' => [
                'enabled' => false,
                'options' => [
                    'client-id' => '',
                    'client-secret' => '',
                ],
            ],
            'viadeo' => [
                'enabled' => false,
                'options' => [
                    'client-id' => '',
                    'client-secret' => '',
                ],
            ],
            'linkedin' => [
                'enabled' => false,
                'options' => [
                    'client-id' => '',
                    'client-secret' => '',
                ],
            ],
        ],
    ],
    'registration-fields' => [
        [
            'name' => 'company',
            'required' => true,
        ],
        [
            'name' => 'lastname',
            'required' => true,
        ],
        [
            'name' => 'firstname',
            'required' => true,
        ],
        [
            'name' => 'geonameid',
            'required' => true,
        ],
    ],
    'xsendfile' => [
        'enabled' => false,
        'type' => 'nginx',
        'mapping' => [
        ],
    ],
    'h264-pseudo-streaming' => [
        'enabled' => false,
        'type' => 'nginx',
        'mapping' => [
        ],
    ],
    'plugins' => [
    ],
    'api_cors' => [
        'enabled' => false,
        'allow_credentials' => false,
        'allow_origin' => [
        ],
        'allow_headers' => [
        ],
        'allow_methods' => [
        ],
        'expose_headers' => [
        ],
        'max_age' => 0,
        'hosts' => [
        ],
    ],
    'session' => [
        'idle' => 0,
        'lifetime' => 604800,
    ],
    'crossdomain' => [
        'allow-access-from' => [
            [
                'domain' => '*.cooliris.com',
                'secure' => 'false',
            ],
        ],
    ],
    'embed_bundle' => [
        'video' => [
            'player' => 'videojs',
            'autoplay' => false,
            'coverSubdef' => 'thumbnail',
            'available-speeds' => [
                1,
                '1.5',
                3,
            ],
        ],
        'audio' => [
            'player' => 'videojs',
            'autoplay' => false,
        ],
        'document' => [
            'player' => 'flexpaper',
            'enable-pdfjs' => true,
        ],
    ],
    'geocoding-providers' => [
        [
            'name' => 'mapBox',
            'enabled' => true,
            'public-key' => '',
            'default-position' => [
                '2.335062',
                '48.879162',
            ],
            'default-zoom' => 2,
            'marker-default-zoom' => 11,
            'position-fields' => [
                [
                    'name' => 'GpsCompositePosition',
                    'type' => 'latlng',
                ],
            ],
        ],
    ],
    'video-editor' => [
        'vttFieldName' => 'VideoTextTrackChapters',
        'seekBackwardStep' => 1000,
        'seekForwardStep' => 1000,
        'playbackRates' => [
            1,
            '1.5',
            3,
        ],
    ],
    'registry' => [
        'general' => [
            'title' => 'Phraseanet',
            'keywords' => null,
            'description' => null,
            'analytics' => null,
            'allow-indexation' => true,
            'home-presentation-mode' => 'GALLERIA',
            'default-subdef-url-ttl' => 7200,
        ],
        'modules' => [
            'thesaurus' => true,
            'stories' => true,
            'doc-substitution' => true,
            'thumb-substitution' => true,
            'anonymous-report' => false,
        ],
        'actions' => [
            'download-max-size' => 120,
            'validation-reminder-days' => 2,
            'validation-expiration-days' => 10,
            'auth-required-for-export' => true,
            'tou-validation-required-for-export' => false,
            'export-title-choice' => false,
            'default-export-title' => 'title',
            'social-tools' => 'none',
            'enable-push-authentication' => false,
            'force-push-authentication' => false,
            'enable-feed-notification' => true,
        ],
        'ftp' => [
            'ftp-enabled' => false,
            'ftp-user-access' => false,
        ],
        'registration' => [
            'auto-select-collections' => true,
            'auto-register-enabled' => false,
        ],
        'maintenance' => [
            'message' => 'The application is down for maintenance',
            'enabled' => false,
        ],
        'api-clients' => [
            'api-enabled' => true,
            'navigator-enabled' => true,
            'office-enabled' => true,
            'adobe_cc-enabled' => true,
        ],
        'webservices' => [
            'google-charts-enabled' => true,
            'geonames-server' => 'http://geonames.alchemyasp.com/',
            'captchas-enabled' => false,
            'recaptcha-public-key' => '',
            'recaptcha-private-key' => '',
        ],
        'executables' => [
            'h264-streaming-enabled' => false,
            'auth-token-directory' => null,
            'auth-token-directory-path' => null,
            'auth-token-passphrase' => null,
            'php-conf-path' => null,
            'imagine-driver' => '',
            'ffmpeg-threads' => 2,
            'pdf-max-pages' => 5,
        ],
        'searchengine' => [
            'min-letters-truncation' => 1,
            'default-query' => '',
            'default-query-type' => 0,
        ],
        'email' => [
            'emitter-email' => 'phraseanet@example.com',
            'prefix' => null,
            'smtp-enabled' => false,
            'smtp-auth-enabled' => false,
            'smtp-host' => null,
            'smtp-port' => null,
            'smtp-secure-mode' => 'tls',
            'smtp-user' => null,
            'smtp-password' => null,
        ],
        'custom-links' => [
            [
                'linkName' => 'Phraseanet store',
                'linkLanguage' => 'fr',
                'linkUrl' => 'https://alchemy.odoo.com/shop',
                'linkLocation' => 'help-menu',
                'linkOrder' => '1',
            ],
            [
                'linkName' => 'Phraseanet store',
                'linkLanguage' => 'en',
                'linkUrl' => 'https://alchemy.odoo.com/en_US/shop',
                'linkLocation' => 'help-menu',
                'linkOrder' => '1',
            ],
        ],
    ],
];
