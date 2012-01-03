<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application,
    Silex\ControllerProviderInterface,
    Silex\ControllerCollection;
use Alchemy\Phrasea\Helper\Record as RecordHelper,
    Alchemy\Phrasea\Out\Module\PDF as PDFExport;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Push implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/', function() use ($app)
            {
              $pusher = new RecordHelper\Push($app['Core']);

              $template = 'prod/actions/printer_default.html.twig';

              $twig = new \supertwig();

              return $twig->render($template, array('printer' => $printer, 'message' => ''));
            }
    );
    $controllers->get('/send/', function() use ($app)
            {
              $pusher = new RecordHelper\Push($app['Core']);
            }
    );
    
    $controllers->get('/validate/', function() use ($app)
            {
              $pusher = new RecordHelper\Push($app['Core']);
            }
    );



    return $controllers;
  }

}
