<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Alchemy\Phrasea\Border\Checker;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class patch_370a9 implements patchInterface
{
    /**
     *
     * @var string
     */
    private $release = '3.7.0.0.a8';

    /**
     *
     * @var Array
     */
    private $concern = array(base::APPLICATION_BOX);

    /**
     *
     * @return string
     */
    public function get_release()
    {
        return $this->release;
    }

    public function require_all_upgrades()
    {
        return false;
    }

    /**
     *
     * @return Array
     */
    public function concern()
    {
        return $this->concern;
    }

    /**
     * Add new border manager service to services.yml & config.yml configuration files
     * @param base $appbox
     */
    public function apply(base &$appbox)
    {
        $core = bootstrap::getCore();

        $services = $core->getConfiguration()->getServices();

        if ( ! isset($services['Border'])) {
            $services['Border'] = array(
                'border_manager' => array(
                    'type'    => 'Border\\BorderManager',
                    'options' => array(
                        'enabled'  => true,
                        'checkers' => array(
                            array(
                                'type'    => 'Checker\\Sha256',
                                'enabled' => true,
                            ),
                            array(
                                'type'    => 'Checker\\UUID',
                                'enabled' => true,
                            ),
                            array(
                                'type'    => 'Checker\\Colorspace',
                                'enabled' => false,
                                'options' => array(
                                    'colorspaces' => array(
                                        Checker\Colorspace::COLORSPACE_CMYK,
                                        Checker\Colorspace::COLORSPACE_GRAYSCALE,
                                        Checker\Colorspace::COLORSPACE_RGB,
                                    )
                                ),
                            ),
                            array(
                                'type'    => 'Checker\\Dimension',
                                'enabled' => false,
                                'options' => array(
                                    'width'  => 80,
                                    'height' => 80,
                                ),
                            ),
                            array(
                                'type'    => 'Checker\\Extension',
                                'enabled' => false,
                                'options' => array(
                                ),
                            ),
                            array(
                                'type'    => 'Checker\\Filename',
                                'enabled' => false,
                                'options' => array(
                                    'sensitive' => false,
                                ),
                            ),
                            array(
                                'type'    => 'Checker\\MediaType',
                                'enabled' => false,
                                'options' => array(
                                    'mediatypes' => array(
                                        Checker\MediaType::TYPE_AUDIO,
                                        Checker\MediaType::TYPE_DOCUMENT,
                                        Checker\MediaType::TYPE_FLASH,
                                        Checker\MediaType::TYPE_IMAGE,
                                        Checker\MediaType::TYPE_VIDEO,
                                    )
                                ),
                            )
                        )
                    )
                )
            );
        }

        $services = $core->getConfiguration()->setServices($services);

        $configs = $core->getConfiguration()->getConfigurations();

        $envs = array('prod', 'dev', 'test');

        foreach ($envs as $env) {
            if (isset($configs[$env]) && is_array($configs[$env]) && ! isset($configs[$env]['border-manager'])) {
                $configs[$env]['border-manager'] = 'border_manager';
            }
        }

        $core->getConfiguration()->setConfigurations($configs);
    }
}

