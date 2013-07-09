<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Alchemy\Phrasea\Utilities\Less\Compiler as LessCompiler;

class LessCompilerServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['phraseanet.less-compiler'] = $app->share(function($app) {
            return LessCompiler::create($app);
        });
    }

    public function boot(Application $app)
    {
    }
}