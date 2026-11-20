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
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Silex\Application;
use Twig\Environment;
use Twig\TwigFilter;

class TwigServiceProvider implements ServiceProviderInterface
{

    private $metricTrans;

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     */
    public function register(Container $app)
    {
        $app->extend('twig', function (Environment $twig, $app) {
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

            $this->metricTrans = [
                'nb_plays'                      => $app['translator']->trans('matomomediametrics:: nb_plays'),
                'nb_unique_visitors_plays'      => $app['translator']->trans('matomomediametrics:: nb_unique_visitors_plays'),
                'nb_impressions'                => $app['translator']->trans('matomomediametrics:: nb_impressions'),
                'nb_unique_visitors_impressions'=> $app['translator']->trans('matomomediametrics:: nb_unique_visitors_impressions'),
                'nb_finishes'                   => $app['translator']->trans('matomomediametrics:: nb_finishes'),
                'sum_time_progress'             => $app['translator']->trans('matomomediametrics:: sum_time_progress'),
                'nb_plays_with_tip'             => $app['translator']->trans('matomomediametrics:: nb_plays_with_tip'),
                'nb_plays_with_ml'              => $app['translator']->trans('matomomediametrics:: nb_plays_with_ml'),
                'sum_fullscreen_plays'          => $app['translator']->trans('matomomediametrics:: sum_fullscreen_plays'),
                'play_rate'                     => $app['translator']->trans('matomomediametrics:: play_rate'),
                'finish_rate'                   => $app['translator']->trans('matomomediametrics:: finish_rate'),
                'fullscreen_rate'               => $app['translator']->trans('matomomediametrics:: fullscreen_rate'),
                'avg_time_watched'              => $app['translator']->trans('matomomediametrics:: avg_time_watched'),
                'avg_completion_rate'           => $app['translator']->trans('matomomediametrics:: avg_completion_rate'),
                'avg_time_to_play'              => $app['translator']->trans('matomomediametrics:: avg_time_to_play'),
                'avg_media_length'              => $app['translator']->trans('matomomediametrics:: avg_media_length'),
            ];

            $this->registerExtensions($twig, $app);

            $this->registerFilters($twig, $app);

            return $twig;
        });
    }

    private function registerExtensions(Environment $twig, Application $app)
    {
        $twig->addExtension(new JSUniqueID());
        $twig->addExtension(new Fit());
        $twig->addExtension(new Camelize());
        $twig->addExtension(new BytesConverter());
        $twig->addExtension(new PhraseanetExtension($app));
    }

    private function registerFilters(Environment $twig, Application $app)
    {
        $twig->addFilter(new TwigFilter('serialize', 'serialize'));
        $twig->addFilter(new TwigFilter('stristr', 'stristr'));
        $twig->addFilter(new TwigFilter('get_class', 'get_class'));
        $twig->addFilter(new TwigFilter('stripdoublequotes', 'stripdoublequotes'));
        $twig->addFilter(new TwigFilter('get_collection_logo', 'collection::getLogo'));
        $twig->addFilter(new TwigFilter('floor', 'floor'));
        $twig->addFilter(new TwigFilter('ceil', 'ceil'));
        $twig->addFilter(new TwigFilter('max', 'max'));
        $twig->addFilter(new TwigFilter('min', 'min'));
        $twig->addFilter(new TwigFilter('bas_labels', 'phrasea::bas_labels'));
        $twig->addFilter(new TwigFilter('sbas_names', 'phrasea::sbas_names'));
        $twig->addFilter(new TwigFilter('sbas_labels', 'phrasea::sbas_labels'));
        $twig->addFilter(new TwigFilter('sbas_from_bas', 'phrasea::sbasFromBas'));
        $twig->addFilter(new TwigFilter('key_exists', 'array_key_exists'));
        $twig->addFilter(new TwigFilter('round', 'round'));
        $twig->addFilter(new TwigFilter('count', 'count'));
        $twig->addFilter(new TwigFilter('formatOctets', 'p4string::format_octets'));
        $twig->addFilter(new TwigFilter('base_from_coll', 'phrasea::baseFromColl'));
        $twig->addFilter(new TwigFilter('escapeSimpleQuote', function ($value) {
            return str_replace("'", "\\'", $value);
        }));

        $twig->addFilter(new TwigFilter('highlight', function (Environment $twig, $string) {
            return str_replace(['[[em]]', '[[/em]]'], ['<em>', '</em>'], $string);
        }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new TwigFilter('linkify', function (Environment $twig, $string) use ($app) {
            return preg_replace(
                "/(\\W|^)(https?:\/{2,4}[\\w:#!%\/;$()~_?\/\-=\\\.&]+)/m"
                ,
                '$1$2 <a title="' . $app['translator']->trans('Open the URL in a new window') . '" class=" fa fa-external-link" href="$2" style="font-size:1.2em;display:inline;padding:2px 5px;margin:0 4px 0 2px;" target="_blank"> &nbsp;</a>$7'
                , $string
            );
        }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new TwigFilter('parseColor', function (Environment $twig, $string) use ($app) {
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

        $twig->addFilter(new TwigFilter('mediaMetricsInfo', function (Environment $twig, $string) use ($app) {
            $metricInfo = $string;

            if (!empty($string)) {
                $metricsInfos = json_decode(html_entity_decode(trim($string)), true);
                if ($metricsInfos != null) {
                    $metricInfo = [];
                    foreach ($metricsInfos as $key => $value) {
                        if (isset($this->metricTrans[trim($key)])) {
                            $metricInfo[] = '<i>' .$this->metricTrans[trim($key)] . ' </i>: ' . $value;
                        }
                    }
                    $metricInfo = implode('<br>', $metricInfo);
                }
            }

            return $metricInfo;
        }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new TwigFilter('bounce',
            function (Environment $twig, $fieldValue, $fieldName, $searchRequest, $sbasId) {
                // bounce value if it is present in thesaurus as well
                return "<a class=\"bounce\" onclick=\"bounce('" . $sbasId . "','"
                . str_replace("'", "\\'", $searchRequest)
                . "', '"
                . str_replace("'", "\\'", $fieldName)
                . "');return(false);\">"
                . $fieldValue
                . "</a>";

            }, ['needs_environment' => true, 'is_safe' => ['html']]));

        $twig->addFilter(new TwigFilter('escapeDoubleQuote', function ($value) {
            return str_replace('"', '\"', $value);
        }));

        $twig->addFilter(new TwigFilter('formatDuration',
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
}
