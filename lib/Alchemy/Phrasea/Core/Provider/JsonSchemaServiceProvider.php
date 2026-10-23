<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Helper\JsonBodyHelper;
use JsonSchema\Constraints\Factory;
use JsonSchema\SchemaStorage;
use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;

class JsonSchemaServiceProvider implements ServiceProviderInterface
{
    public function register(Container $app)
    {
        $app['json-schema.base_uri'] = 'file://' . realpath(__DIR__ . '/../../../../../lib/conf.d/json_schema') . '/';
        $app['json-schema.retriever'] = function () {
            return new UriRetriever();
        };

        $app['json-schema.storage'] = function (Application $app) {
            return new SchemaStorage($app['json-schema.retriever']);
        };

        $app['json-schema.constraint_factory'] = function (Application $app) {
            return new Factory($app['json-schema.storage'], $app['json-schema.retriever']);
        };

        $app['json-schema.validator'] = $app->factory(function (Application $app) {
            return new Validator($app['json-schema.constraint_factory']);
        });

        $app['json.body_helper'] = function (Application $app) {
            return new JsonBodyHelper(
                $app['json-schema.storage'],
                $app['json-schema.constraint_factory'],
                $app['json-schema.base_uri']
            );
        };
    }
}
