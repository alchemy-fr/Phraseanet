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

class DisplaySettingService
{
    /**
     * The default user settings.
     *
     * @var array
     */
    private static $defaultUserSettings = [
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
     * A merge of default user settings and configuration customisation.
     *
     * @var Array
     */
    private $usersSettings;

    /** @var PropertyAccess */
    private $conf;

    public function __construct(PropertyAccess $conf)
    {
        $this->conf = $conf;
    }

    /**
     * Returns user settings.
     *
     * @return array
     */
    public function getUsersSettings()
    {
        $this->loadUsersSettings();

        return $this->usersSettings;
    }

    /**
     * Return a user setting given a user.
     *
     * @param User $user
     * @param string $name
     * @param mixed $default
     *
     * @return mixed
     */
    public function getUserSetting(User $user, $name, $default = null)
    {
        if (false === $user->getSettings()->containsKey($name)) {
            $this->loadusersSettings();

            return array_key_exists($name, $this->usersSettings) ? $this->usersSettings[$name] : $default;
        }

        return $user->getSettings()->get($name)->getValue();
    }

    /**
     * Returns application setting value.
     *
     * @param string|array $props
     * @param mixed $default
     *
     * @return mixed
     */
    public function getApplicationSetting($props, $default = null)
    {
        return $this->conf->get(array_merge(['registry'], is_array($props) ? $props : [$props]), $default);
    }

    /**
     * Merge default user settings and configuration customisation.
     */
    private function loadUsersSettings()
    {
        if (null !== $this->usersSettings) {
            return;
        }

        $this->usersSettings = array_replace(
            self::$defaultUserSettings,
            // removes undefined keys in default settings
            array_intersect_key(
                $this->conf->get(['user-settings'], []),
                self::$defaultUserSettings
            )
        );
    }
}
