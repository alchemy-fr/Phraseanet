<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use JsonSchema\RefResolver;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Webmozart\Json\JsonDecoder;
use Webmozart\Json\JsonEncoder;
use Webmozart\Json\JsonValidator;

class JsonSchemaServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['json-schema.retriever'] = $app->share(function () {
            return new UriRetriever();
        });

        $app['json-schema.ref_resolver'] = $app->share(function (Application $app) {
            return new RefResolver($app['json-schema.retriever']);
        });

        $app['json-schema.validator'] = $app->share(function (Application $app) {
            return new Validator(Validator::CHECK_MODE_NORMAL, $app['json-schema.retriever']);
        });

        $app['json.validator'] = $app->share(function (Application $app) {
            return new JsonValidator($app['json-schema.validator']);
        });
        $app['json.decoder'] = $app->share(function (Application $app) {
            return new JsonDecoder($app['json.validator']);
        });
        $app['json.encoder'] = $app->share(function (Application $app) {
            return new JsonEncoder($app['json.validator']);
        });
        $app['json-schema.base_uri'] = $app->share(function (Application $app) {
            return 'file://' . $app['root.path'] . '/lib/conf.d/json_schema/';
        });
    }

    public function boot(Application $app)
    {
    }
}
