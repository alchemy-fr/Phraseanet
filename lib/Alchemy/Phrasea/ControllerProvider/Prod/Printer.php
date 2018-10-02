<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\ControllerProvider\Prod;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Prod\PrinterController;
use Alchemy\Phrasea\ControllerProvider\ControllerProviderTrait;
use Silex\Application;
use Silex\ControllerProviderInterface;
use Silex\ServiceProviderInterface;

class Printer implements ControllerProviderInterface, ServiceProviderInterface
{
    use ControllerProviderTrait;

    public function register(Application $app)
    {
        $app['controller.prod.printer'] = $app->share(function (PhraseaApplication $app) {
            return (new PrinterController($app))
                ->setDataboxLoggerLocator($app['phraseanet.logger'])
            ;
        });
    }

    public function boot(Application $app)
    {
        // no-op
    }

    public function connect(Application $app)
    {
        $controllers = $this->createCollection($app);
        $controllers->post('/', 'controller.prod.printer:postPrinterAction');

        $controllers->post('/print.pdf', 'controller.prod.printer:printAction')
            ->bind('prod_printer_print');

        return $controllers;
    }
}
