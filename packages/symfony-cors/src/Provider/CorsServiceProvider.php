<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\CorsProvider;

use Alchemy\Cors\Configuration\CorsConfiguration;
use Alchemy\Cors\CorsListener;
use Alchemy\Cors\Options\DefaultProvider;
use Alchemy\Cors\Options\DefaultResolver;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Api\BootableProviderInterface;
use Silex\Application;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\HttpKernel\KernelEvents;

class CorsServiceProvider implements ServiceProviderInterface, BootableProviderInterface
{
    public function register(Container $app)
    {
        $app['alchemy_cors.cache_path'] = null;
        $app['alchemy_cors.debug'] = false;

        $app['alchemy_cors.defaults'] = array();
        $app['alchemy_cors.paths'] = array();

        $app['alchemy_cors.config'] = function (Container $app) {
            $config = null;

            if ('' != $cachePath = $app['alchemy_cors.cache_path']) {
                $config = new ConfigCache($cachePath, $app['alchemy_cors.debug']);

                if ($config->isFresh()) {
                    return require $cachePath;
                }
            }

            $processor = new Processor();
            $configuration = new CorsConfiguration();

            $configArray = array(
                'defaults' => $app['alchemy_cors.defaults'],
                'paths'    => $app['alchemy_cors.paths'],
            );

            $processed = $processor->processConfiguration($configuration, array(
                $configArray,
            ));

            if ($config) {
                $configAsArray = var_export($processed, true);
                $configString = <<<CONFIG_EOF
<?php
return {$configAsArray};
CONFIG_EOF;
                $config->write($configString);
            }

            return $processed;
        };

        $app['alchemy_cors.options_provider.config'] = $app->factory(function (Container $app) {
            $config = $app['alchemy_cors.config'];

            return new DefaultProvider($config['paths'], $config['defaults']);
        });
        $app['alchemy_cors.options_providers'] = new \ArrayObject(array(
            'alchemy_cors.options_provider.config',
        ));

        $that = $this;
        $app['alchemy_cors.options_resolver'] = function (Container $app) use ($that) {
            $providers = array();

            foreach ($that->sortProviders($app['alchemy_cors.options_providers']) as $serviceNames) {
                foreach ($serviceNames as $serviceName) {
                    $providers[] = $app[$serviceName];
                }
            }

            return new DefaultResolver($providers);
        };

        $app['alchemy_cors.listener'] = function (Container $app) {
            return new CorsListener($app['dispatcher'], $app['alchemy_cors.options_resolver']);
        };
    }

    public function boot(Application $app)
    {
        $app['dispatcher']->addListener(
            KernelEvents::REQUEST,
            array($app['alchemy_cors.listener'], 'onKernelRequest'),
            10000
        );
    }

    public function sortProviders($providers)
    {
        if (!is_array($providers) && !$providers instanceof \Traversable) {
            throw new \InvalidArgumentException('Providers should be an array or Traversable instance');
        }

        $providersByPriority = array();

        foreach ($providers as $service) {
            $priority = 0;

            if (is_array($service)) {
                if (!isset($service['service'])) {
                    throw new \InvalidArgumentException(
                        'Providers should be a string or an array with provider key'
                    );
                }
                if (isset($service['priority'])) {
                    $priority = $service['priority'];
                }
                $service = $service['service'];
            }

            if (!isset($providersByPriority[$priority])) {
                $providersByPriority[$priority] = array();
            }

            $providersByPriority[$priority][] = $service;
        }

        krsort($providersByPriority);

        return $providersByPriority;
    }
}
