<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Configuration;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\User;

class DisplaySetting
{
    /**
     * The default user base setting values.
     *
     * @var array
     */
    private static $defaultUserBaseSettings = [
        'view'                    => 'thumbs',
        'images_per_page'         => '20',
        'images_size'             => '120',
        'editing_images_size'     => '134',
        'editing_top_box'         => '180px',
        'editing_right_box'       => '400px',
        'editing_left_box'        => '710px',
        'basket_sort_field'       => 'name',
        'basket_sort_order'       => 'ASC',
        'warning_on_delete_story' => 'true',
        'client_basket_status'    => '1',
        'css'                     => '000000',
        'start_page_query'        => 'last',
        'start_page'              => 'QUERY',
        'rollover_thumbnail'      => 'caption',
        'technical_display'       => '1',
        'doctype_display'         => '1',
        'bask_val_order'          => 'nat',
        'basket_caption_display'  => '0',
        'basket_status_display'   => '0',
        'basket_title_display'    => '0'
    ];

    /**
     * A combination of default user base settings and configuration user settings.
     *
     * @var Array
     */
    private $defaultUserSettings;

    /** @var PropertyAccess */
    private $conf;

    public function __construct(PropertyAccess $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Returns default user settings;
     *
     * @return array
     */
    public function getDefaultUserSettings()
    {
        $this->loadDefaultUserSettings();

        return $this->defaultUserSettings;
    }

    /**
     * Returns user setting value.
     *
     * @param User $user
     * @param      $name
     * @param null $default
     *
     * @return mixed
     */
    public function getUserSetting(User $user, $name, $default = null)
    {
        $this->loadDefaultUserSettings();

        return $user->getSettingValue(
            $name,
            array_key_exists($name, $this->defaultUserSettings) ? $this->defaultUserSettings[$name] : $default
        );
    }

    /**
     * Returns application setting value.
     *
     * @param      $props
     * @param null $default
     *
     * @return mixed
     */
    public function getApplicationSetting($props, $default = null)
    {
        return $this->conf->get($props, $default);
    }

    /**
     * Sets defaults settings from base default values and configuration values.
     */
    private function loadDefaultUserSettings()
    {
        if (null !== $this->defaultUserSettings) {
            return;
        }

        $this->defaultUserSettings = array_replace(
            self::$defaultUserBaseSettings,
            // removes undefined keys in default settings
            array_intersect_key(
                $this->getApplicationSetting(['user-settings'], []),
                self::$defaultUserBaseSettings
            )
        );
    }
}
