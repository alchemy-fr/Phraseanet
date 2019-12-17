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

use Alchemy\Phrasea\Border\Manager;
use Alchemy\Phrasea\Border\MimeGuesserConfiguration;
use Silex\Application;
use Silex\ServiceProviderInterface;

class BorderManagerServiceProvider implements ServiceProviderInterface
{

    public function register(Application $app)
    {
        $app['border-manager'] = $app->share(function (Application $app) {
            $borderManager = new Manager($app);

            $options = $app['conf']->get('border-manager');

            $registeredCheckers = [];

            $borderManager->setEnabled(isset($options['enabled']) && $options['enabled']);
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
                                $databoxes[] = $app->findDataboxById($sbas_id);
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
                                $collections[] = \collection::getByBaseId($app, $base_id);
                            } catch (\Exception $e) {
                                throw new \InvalidArgumentException('Invalid collection option');
                            }
                        }

                        $checkerObj->restrictToCollections($collections);
                    }

                    if (isset($checker['compare-ignore-collections'])) {
                        $collections = [];
                        foreach ($checker['compare-ignore-collections'] as $base_id) {
                            try {
                                $collections[] = \collection::getByBaseId($app, $base_id);
                            } catch (\Exception $e) {
                                throw new \InvalidArgumentException('Invalid collection option');
                            }
                        }

                        $checkerObj->setCompareIgnoreCollections($collections);
                    }

                    $registeredCheckers[] = $checkerObj;
                } catch (\InvalidArgumentException $e) {
                    $app['monolog']->error(
                        sprintf('Border manager checker InvalidArgumentException : %s', $e->getMessage())
                    )
                    ;
                } catch (\LogicException $e) {
                    $app['monolog']->error(sprintf('Border manager checker LogicException : %s', $e->getMessage()));
                }
            }
            $borderManager->registerCheckers($registeredCheckers);

            return $borderManager;
        });

        $app['border-manager.mime-guesser-configuration'] = $app->share(function (Application $app) {
            return new MimeGuesserConfiguration($app['conf']);
        });
    }

    public function boot(Application $app)
    {
        $app['border-manager.mime-guesser-configuration']->register();
    }
}
