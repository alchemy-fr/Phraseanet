<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2020 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Application;

class patch_410alpha28a implements patchInterface
{
    /** @var string */
    private $release = '4.1.0-alpha.28a';

    /** @var array */
    private $concern = [base::APPLICATION_BOX];

    /**
     * Returns the release version.
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    /**
     * {@inheritdoc}
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * {@inheritdoc}
     */
    public function require_all_upgrades()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getDoctrineMigrations()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function apply(base $appbox, Application $app)
    {
        //  add geoloc section if not exist
        if (!$app['conf']->has(['geocoding-providers'])) {
            $providers[0] = [
                'map-provider' => 'mapboxWebGL',
                'enabled'      => false,
                'public-key'   => '',
                'map-layers'   => [
                    0 => [
                       'name'   => 'Light',
                       'value'  => 'mapbox://styles/mapbox/light-v9'
                    ],
                    1 => [
                        'name'  => 'Streets',
                        'value' => 'mapbox://styles/mapbox/streets-v9'
                    ],
                    2 => [
                        'name'  => 'Basic',
                        'value' => 'mapbox://styles/mapbox/basic-v9'
                    ],
                    3 => [
                        'name'  => 'Satellite',
                        'value' => 'mapbox://styles/mapbox/satellite-v9'
                    ],
                    4 => [
                        'name'  => 'Dark',
                        'value' => 'mapbox://styles/mapbox/dark-v9'
                    ]
                ],
                'transition-mapboxgl' => [
                    0 => [
                        'animate' => true,
                        'speed'   => '2.2',
                        'curve'   => '1.42'
                    ]
                ],
                'default-position' => [
                    '48.879162',
                    '2.335062'
                ],
                'default-zoom'              => 5,
                'marker-default-zoom'       => 9,
                'position-fields'           => [],
                'geonames-field-mapping'    => true,
                'cityfields'                => 'City, Ville',
                'provincefields'            => 'Province',
                'countryfields'             => 'Country, Pays'
            ];

            $app['conf']->set(['geocoding-providers'], $providers);
        }

        // add video-editor section if not exist
        if (!$app['conf']->has(['video-editor'])) {
            $videoEditor = [
                'ChapterVttFieldName' => 'VideoTextTrackChapters',
                'seekBackwardStep'    => 500,
                'seekForwardStep'     => 500,
                'playbackRates'       => [
                    1,
                    '1.5',
                    3
                ]
            ];

            $app['conf']->set(['video-editor'], $videoEditor);
        }

        //  add api_token_header if not exist
        if (!$app['conf']->has(['main', 'api_token_header'])) {
            $app['conf']->set(['main', 'api_token_header'], false);
        }

        // insert timeout if not exist
        if (!$app['conf']->has(['main', 'binaries', 'ffmpeg_timeout'])) {
            $app['conf']->set(['main', 'binaries', 'ffmpeg_timeout'], 3600);
        }
        if (!$app['conf']->has(['main', 'binaries', 'ffprobe_timeout'])) {
            $app['conf']->set(['main', 'binaries', 'ffprobe_timeout'], 60);
        }
        if (!$app['conf']->has(['main', 'binaries', 'gs_timeout'])) {
            $app['conf']->set(['main', 'binaries', 'gs_timeout'], 60);
        }
        if (!$app['conf']->has(['main', 'binaries', 'mp4box_timeout'])) {
            $app['conf']->set(['main', 'binaries', 'mp4box_timeout'], 60);
        }
        if (!$app['conf']->has(['main', 'binaries', 'swftools_timeout'])) {
            $app['conf']->set(['main', 'binaries', 'swftools_timeout'], 60);
        }
        if (!$app['conf']->has(['main', 'binaries', 'unoconv_timeout'])) {
            $app['conf']->set(['main', 'binaries', 'unoconv_timeout'], 60);
        }
        if (!$app['conf']->has(['main', 'binaries', 'exiftool_timeout'])) {
            $app['conf']->set(['main', 'binaries', 'exiftool_timeout'], 60);
        }

        // custom-link section, remove default store
        $app['conf']->remove(['registry', 'custom-links', 0]);
        $app['conf']->remove(['registry', 'custom-links', 1]);

        $customLinks = [
            'linkName'      => 'Phraseanet store',
            'linkLanguage'  => 'all',
            'linkUrl'       => 'https://store.alchemy.fr',
            'linkLocation'  => 'help-menu',
            'linkOrder'     =>  1,
            'linkBold'      =>  false,
            'linkColor'     =>  ''
        ];

        $app['conf']->set(['registry', 'custom-links', 0], $customLinks);

        return true;
    }
}
