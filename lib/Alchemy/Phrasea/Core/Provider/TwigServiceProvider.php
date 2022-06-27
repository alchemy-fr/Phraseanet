<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Provider;

use Alchemy\Phrasea\Twig\BytesConverter;
use Alchemy\Phrasea\Twig\Camelize;
use Alchemy\Phrasea\Twig\Fit;
use Alchemy\Phrasea\Twig\JSUniqueID;
use Alchemy\Phrasea\Twig\PhraseanetExtension;
use Silex\Application;
use Silex\ServiceProviderInterface;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

class TwigServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Application $app)
    {
        $app['twig'] = $app->share($app->extend('twig', function (\Twig_Environment $twig, $app) {
            $twig->setCache($app['cache.path'] . '/twig');

            $paths = [];

            if (file_exists($app['plugin.path'] . '/twig-paths.php')) {
                $paths = require $app['plugin.path'] . '/twig-paths.php';
            }

            if ($app['browser']->isTablet() || $app['browser']->isMobile()) {
//                $paths[] = $app['root.path'] . '/config/templates/mobile';
                $paths[] = $app['root.path'] . '/templates/mobile';
                $paths['phraseanet'] = $app['root.path'] . '/config/templates/mobile';
                $paths['phraseanet'] = $app['root.path'] . '/templates/mobile';
            }

//            $paths[] = $app['root.path'] . '/config/templates/web';
            $paths[] = $app['root.path'] . '/templates/web';
            $paths['phraseanet'] = $app['root.path'] . '/config/templates/web';
            $paths['phraseanet'] = $app['root.path'] . '/templates/web';

            foreach ($paths as $namespace => $path) {
                if (!is_int($namespace)) {
                    $app['twig.loader.filesystem']->addPath($path, $namespace);
                } else {
                    $app['twig.loader.filesystem']->addPath($path);
                }
            }

            $twig->addGlobal('current_date', new \DateTime());

            $this->registerExtensions($twig, $app);
            $this->registerFilters($twig, $app);

            return $twig;
        }));
    }

    private function registerExtensions(\Twig_Environment $twig, Application $app)
    {
        $twig->addExtension(new \Twig_Extension_Core());
        $twig->addExtension(new \Twig_Extension_Optimizer());
        $twig->addExtension(new \Twig_Extension_Escaper());
        if ($app['debug']) {
            $twig->addExtension(new \Twig_Extension_Debug());
        }

        // add filter trans
        $twig->addExtension(new TranslationExtension($app['translator']));
        // add filter localizeddate
        $twig->addExtension(new \Twig_Extensions_Extension_Intl());
        // add filters truncate, wordwrap, nl2br
        $twig->addExtension(new \Twig_Extensions_Extension_Text());
        $twig->addExtension(new JSUniqueID());
        $twig->addExtension(new Fit());
        $twig->addExtension(new Camelize());
        $twig->addExtension(new BytesConverter());
        $twig->addExtension(new PhraseanetExtension($app));
    }

    private function registerFilters(\Twig_Environment $twig, Application $app)
    {
        $twig->addFilter('serialize', new \Twig_Filter_Function('serialize'));
        $twig->addFilter('stristr', new \Twig_Filter_Function('stristr'));
        $twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
        $twig->addFilter('stripdoublequotes', new \Twig_Filter_Function('stripdoublequotes'));
        $twig->addFilter('get_collection_logo', new \Twig_Filter_Function('collection::getLogo'));
        $twig->addFilter('floor', new \Twig_Filter_Function('floor'));
        $twig->addFilter('ceil', new \Twig_Filter_Function('ceil'));
        $twig->addFilter('max', new \Twig_Filter_Function('max'));
        $twig->addFilter('min', new \Twig_Filter_Function('min'));
        $twig->addFilter('bas_labels', new \Twig_Filter_Function('phrasea::bas_labels'));
        $twig->addFilter('sbas_names', new \Twig_Filter_Function('phrasea::sbas_names'));
        $twig->addFilter('sbas_labels', new \Twig_Filter_Function('phrasea::sbas_labels'));
        $twig->addFilter('sbas_from_bas', new \Twig_Filter_Function('phrasea::sbasFromBas'));
        $twig->addFilter('key_exists', new \Twig_Filter_Function('array_key_exists'));
        $twig->addFilter('round', new \Twig_Filter_Function('round'));
        $twig->addFilter('count', new \Twig_Filter_Function('count'));
        $twig->addFilter('formatOctets', new \Twig_Filter_Function('p4string::format_octets'));
        $twig->addFilter('base_from_coll', new \Twig_Filter_Function('phrasea::baseFromColl'));
        $twig->addFilter(new \Twig_SimpleFilter('escapeSimpleQuote', function ($value) {
            return str_replace("'", "\\'", $value);
        }));

        $twig->addFilter(new \Twig_SimpleFilter('highlight', function (\Twig_Environment $twig, $string) {
            return str_replace(['[[em]]', '[[/em]]'], ['<em>', '</em>'], $string);
        }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new \Twig_SimpleFilter('linkify', function (\Twig_Environment $twig, $string) use ($app) {
            return preg_replace(
                "/(\\W|^)(https?:\/{2,4}[\\w:#!%\/;$()~_?\/\-=\\\.&]+)/m"
                ,
                '$1$2 <a title="' . $app['translator']->trans('Open the URL in a new window') . '" class=" fa fa-external-link" href="$2" style="font-size:1.2em;display:inline;padding:2px 5px;margin:0 4px 0 2px;" target="_blank"> &nbsp;</a>$7'
                , $string
            );
        }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new \Twig_SimpleFilter('parseColor', function (\Twig_Environment $twig, $string) use ($app) {
            $re = '/^(.*)\[#([0-9a-fA-F]{6})]$/m';
            $stringArr = explode(';', $string);

            foreach ($stringArr as $key => $value) {
                preg_match_all($re, trim($value), $matches);
                if ($matches && $matches[1] != null && $matches[2] != null) {
                    $colorCode = '#' . $matches[2][0];
                    $colorName = $matches[1][0];

                    $stringArr[$key] = '<span style="white-space: nowrap;"><span class="color-dot" style="margin-right: 4px; background-color: ' . $colorCode . '"></span>' . $colorName . '</span>';
                }
            }

            return implode('; ', $stringArr);
        }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new \Twig_SimpleFilter('bounce',
            function (\Twig_Environment $twig, $fieldValue, $fieldName, $searchRequest, $sbasId) {
                // bounce value if it is present in thesaurus as well
                return "<a class=\"bounce\" onclick=\"bounce('" . $sbasId . "','"
                . str_replace("'", "\\'", $searchRequest)
                . "', '"
                . str_replace("'", "\\'", $fieldName)
                . "');return(false);\">"
                . $fieldValue
                . "</a>";

            }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new \Twig_SimpleFilter('escapeDoubleQuote', function ($value) {
            return str_replace('"', '\"', $value);
        }));

        $twig->addFilter(new \Twig_SimpleFilter('formatDuration',
            function ($secondsInDecimals) {
                $time = [];
                $hours = floor($secondsInDecimals / 3600);
                $secondsInDecimals -= $hours * 3600;
                $minutes = floor($secondsInDecimals / 60);
                $secondsInDecimals -= $minutes * 60;
                $seconds = intVal($secondsInDecimals % 60, 10);
                if ($hours > 0) {
                    array_push($time, (strlen($hours) < 2) ? "0{$hours}" : $hours);
                }
                array_push($time, (strlen($minutes) < 2) ? "0{$minutes}" : $minutes);
                array_push($time, (strlen($seconds) < 2) ? "0{$seconds}" : $seconds);
                $formattedTime = implode(':', $time);

                return $formattedTime;
            }
        ));
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registered
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        // no-op
    }
}
