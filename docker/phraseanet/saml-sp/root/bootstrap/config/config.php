<?php

/*
 * SimpleSAMLphp 2.x configuration of the phraseanet service provider.
 *
 * This file is a template : if the simplesamlphp config dir (config/plugins/saml-authentication, shared with
 * phraseanet) has no config.php yet, the entrypoint replaces the ${SAML_...} variables and writes the result there.
 * An existing config.php is never modified.
 *
 * The sessions are stored in redis ("redis" store) so they are shared by this container and phraseanet
 * without using the php sessions.
 */

$config = [
    'baseurlpath' => '${SAML_PHRASEANET_HOST}/simplesaml/',

    'certdir' => 'cert/',
    'loggingdir' => 'log/',
    'datadir' => 'data/',
    'tempdir' => '/tmp/simplesaml',

    'technicalcontact_name' => 'Administrator',
    'technicalcontact_email' => 'na@example.org',

    'secretsalt' => '${SAML_SECRET_SALT}',
    'auth.adminpassword' => '${SAML_ADMIN_PASSWORD}',
    'admin.protectmetadata' => false,

    'timezone' => null,

    'debug' => [
        'saml' => ${SAML_DEBUG},
        'backtraces' => true,
        'validatexml' => false,
    ],
    'showerrors' => ${SAML_DEBUG},
    'errorreporting' => false,

    'logging.level' => \SimpleSAML\Logger::NOTICE,
    'logging.handler' => 'errorlog',
    'logging.processname' => 'simplesamlphp',

    'enable.saml20-idp' => false,
    'enable.adfs-idp' => false,

    'module.enable' => [
        'exampleauth' => false,
        'core' => true,
        'admin' => true,
        'saml' => true,
    ],

    'session.duration' => 8 * (60 * 60), // 8 hours.
    'session.datastore.timeout' => (4 * 60 * 60), // 4 hours
    'session.state.timeout' => (60 * 60), // 1 hour
    'session.cookie.name' => 'SimpleSAMLSessionID',
    'session.cookie.lifetime' => 0,
    'session.cookie.path' => '/',
    'session.cookie.domain' => null,
    'session.cookie.secure' => ${SAML_COOKIE_SECURE},
    'session.cookie.samesite' => ${SAML_COOKIE_SAMESITE},
    'session.authtoken.cookiename' => 'SimpleSAMLAuthToken',

    'language.available' => ['en', 'fr', 'de', 'nl'],
    'language.default' => 'en',

    'metadata.sources' => [
        ['type' => 'flatfile'],
    ],

    'store.type' => 'redis',
    'store.redis.host' => '${SAML_REDIS_HOST}',
    'store.redis.port' => ${SAML_REDIS_PORT},
    'store.redis.prefix' => 'SimpleSAMLphp',
];
