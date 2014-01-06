<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Border\Manager;
use Silex\Application;
use Silex\ServiceProviderInterface;
use XPDF\Exception\BinaryNotFoundException;

class BorderManagerServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['border-manager'] = $app->share(function (Application $app) {
            $borderManager = new Manager($app);

            try {
                $borderManager->setPdfToText($app['xpdf.pdftotext']);
            } catch (BinaryNotFoundException $e) {

            }

            $options = $app['conf']->get('border-manager');

            $registeredCheckers = [];

            if ($options['enabled']) {
                foreach ($options['checkers'] as $checker) {
                    if (!isset($checker['type'])) {
                        continue;
                    }
                    if (isset($checker['enabled']) && $checker['enabled'] !== true) {
                        continue;
                    }

                    $className = sprintf('\\Alchemy\\Phrasea\\Border\\%s', $checker['type']);

                    if (!class_exists($className)) {
                        $app['monolog']->error(sprintf('Border manager checker, invalid checker %s', $checker['type']));
                        continue;
                    }

                    $options = [];

                    if (isset($checker['options']) && is_array($checker['options'])) {
                        $options = $checker['options'];
                    }

                    try {
                        $checkerObj = new $className($app, $options);
                        if (isset($checker['databoxes'])) {

                            $databoxes = [];
                            foreach ($checker['databoxes'] as $sbas_id) {
                                try {
                                    $databoxes[] = $app['phraseanet.appbox']->get_databox($sbas_id);
                                } catch (\Exception $e) {
                                    throw new \InvalidArgumentException('Invalid databox option');
                                }
                            }

                            $checkerObj->restrictToDataboxes($databoxes);
                        }
                        if (isset($checker['collections'])) {

                            $collections = [];
                            foreach ($checker['collections'] as $base_id) {
                                try {
                                    $collections[] = \collection::get_from_base_id($app, $base_id);
                                } catch (\Exception $e) {
                                    throw new \InvalidArgumentException('Invalid collection option');
                                }
                            }

                            $checkerObj->restrictToCollections($collections);
                        }
                        $registeredCheckers[] = $checkerObj;
                    } catch (\InvalidArgumentException $e) {
                        $app['monolog']->error(sprintf('Border manager checker InvalidArgumentException : %s', $e->getMessage()));
                    } catch (\LogicException $e) {
                        $app['monolog']->error(sprintf('Border manager checker LogicException : %s', $e->getMessage()));
                    }
                }

                $borderManager->registerCheckers($registeredCheckers);
            }

            return $borderManager;
        });
    }

    public function boot(Application $app)
    {
    }
}
