<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Plugin;

interface ActionBarPluginInterface
{
    /**
     * Get the action bar definition
     *
     * [
     *      'push' => [
     *          '{actionKey}' => [
     *              'classes' => '{string with css classes}',
     *              'icon' => '{icon asset name}',
     *              'label' => '{translation key}',
     *          ],
     *     ],
     * ]
     *
     * @return array
     */
    public function getActionBar();

    /**
     * @return string
     */
    public function getPluginLocale();

    /**
     * @return string
     */
    public function getPluginName();
}
