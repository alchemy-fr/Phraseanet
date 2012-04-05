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
use Symfony\Component\HttpFoundation\Request,
    Symfony\Component\HttpFoundation\Response,
    Symfony\Component\HttpFoundation\RedirectResponse,
    Symfony\Component\HttpKernel\Exception\HttpException,
    Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Alchemy\Phrasea\RouteProcessor\Basket as BasketRoute,
    Alchemy\Phrasea\Helper;

/**
 *
 * @package
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Tools implements ControllerProviderInterface
{

  public function connect(Application $app)
  {
    $controllers = new ControllerCollection();

    $controllers->get('/', function(Application $app, Request $request)
            {
              $helper = new Helper\Record\Tools($app['Core'], $request);

              $selection = $helper->get_elements();

              $binary = $app['Core']->getRegistry()->get('GV_exiftool');

              $metadatas = false;
              $record = null;
              $metadatasFirst = $metadatasSecond = array();
               
              if (count($selection) == 1 && ! empty($binary))
              {
                try
                {
                  $record = reset($selection);
                  $file = $record->get_subdef('document')->get_pathfile();
                  $cmd = $binary . ' -h ' . escapeshellarg($file);

                  $out = "";
                  exec($cmd, $out);
                  foreach ($out as $liout)
                  {
                    if (strpos($liout, '<tr><td>Directory') === false)
                      $metadatasFirst[] = $liout;
                  }
                  $out = "";
                  $cmd = $binary . ' -X -n -fast ' . escapeshellarg($file) . '';
                  exec($cmd, $out);
                  foreach ($out as $liout)
                  {
                    $metadatasSecond[] = htmlentities($liout);
                  }
                  $metadatas = true;
                }
                catch (\Exception $e)
                {
                  
                }
              }

              $template = 'prod/actions/Tools/index.html.twig';
              
              $var = array(
                  'helper' => $helper,
                  'selection' => $selection,
                  'record' => $record,
                  'metadatas' => $metadatas,
                  'metadatasFirst' => $metadatasFirst,
                  'metadatasSecond' => $metadatasSecond
              );
              return new Response($app['Core']->getTwig()->render($template, $var));
            });

    $controllers->post('/rotate/', function(Application $app, Request $request)
            {
              $return = array('success' => false, 'errorMessage' => '');

              $helper = new Helper\Record\Tools($app['Core'], $request);

              $rotation = in_array($request->get('rotation'), array('-90', '90', '180')) ? $request->get('rotation', 90) : 90;

              $selection = $helper->get_elements();

              foreach ($selection as $record)
              {
                try
                {
                  $record->rotate_subdefs($rotation);
                  $return['success'] = true;
                }
                catch (\Exception $e)
                {
                  $return['errorMessage'] = $e->getMessage();
                }
              }

              $json = $app['Core']->getSerializer()->serialize($return, 'json');
              return new Response($json, 200, array('content-type' => 'application/json'));
            });

    $controllers->post('/image/', function(Application $app, Request $request)
            {
              $return = array('success' => false);

              $helper = new Helper\Record\Tools($app['Core'], $request);

              $selection = $helper->get_elements();

              if ($request->get('ForceThumbSubstit') == '1')
              {
                foreach ($selection as $record)
                {
                  try
                  {
                    $record->rebuild_subdefs();
                    $return['success'] = true;
                  }
                  catch (\Exception $e)
                  {
                    
                  }
                }
              }

              $json = $app['Core']->getSerializer()->serialize($return, 'json');
              return new Response($json, 200, array('content-type' => 'application/json'));
            });

    $controllers->post('/hddoc/', function(Application $app, Request $request)
            {
              $success = false;
              $errorMessage = "";

              if ($file = $request->files->get('newHD'))
              {
                $fileName = $file->getClientOriginalName();
                $size = $file->getClientSize();

                if ($size && $fileName && $file->isValid())
                {

                  try
                  {
                    $record = new \record_adapter(
                                    $request->get('sbas_id')
                                    , $request->get('record_id')
                    );

                    $record->substitute_subdef(
                            'document'
                            , new \system_file($file->getPathname())
                    );

                    if ((int) $request->get('ccfilename') === 1)
                    {
                      $record->set_original_name($fileName);
                    }

                    $success = true;
                  }
                  catch (\Exception $e)
                  {
                    $errorMessage = $e->getMessage();
                  }
                }
                else
                {
                  $errorMessage = _('file is not valid');
                }

                $template = 'prod/actions/Tools/iframeUpload.html.twig';
                $var = array(
                    'success' => $success
                    , 'fileName' => $fileName
                    , 'errorMessage' => $errorMessage
                );
                return new Response($app['Core']->getTwig()->render($template, $var));
              }
            });

    $controllers->post('/chgthumb/', function(Application $app, Request $request)
            {
              $success = false;
              $errorMessage = "";

              if ($file = $request->files->get('newThumb'))
              {

                $size = $file->getClientSize();
                $fileName = $file->getClientOriginalName();

                if ($size && $fileName && $file->isValid())
                {
                  try
                  {
                    $rootPath = $app['Core']->getRegistry()->get('GV_RootPath');
                    $tmpFile = $rootPath . 'tmp/' . $fileName;
                    rename($file->getPathname(), $tmpFile);

                    $record = new \record_adapter(
                                    $request->get('sbas_id')
                                    , $request->get('record_id')
                    );

                    $record->substitute_subdef(
                            'thumbnail'
                            , new \system_file($tmpFile)
                    );

                    $success = true;
                  }
                  catch (\Exception $e)
                  {
                    $errorMessage = $e->getMessage();
                  }
                }
                else
                {
                  $errorMessage = _('file is not valid');
                }

                $template = 'prod/actions/Tools/iframeUpload.html.twig';
                $var = array(
                    'success' => $success
                    , 'fileName' => $fileName
                    , 'errorMessage' => $errorMessage
                );
                return new Response($app['Core']->getTwig()->render($template, $var));
              }
            });
    return $controllers;
  }

}
