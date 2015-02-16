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

use JsonSchema\Uri\UriRetriever;
use JsonSchema\Validator;
use Silex\Application;
use Silex\ServiceProviderInterface;

class JsonSchemaServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['json-schema.retriever'] = $app->share(function (Application $app) {
            return new UriRetriever();
        });

        $app['json-schema.validator'] = $app->share(function (Application $app) {
            return new Validator();
        });
    }

    public function boot(Application $app)
    {
    }
}
