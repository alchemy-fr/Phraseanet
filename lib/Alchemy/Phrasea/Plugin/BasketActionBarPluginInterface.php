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


interface BasketActionBarPluginInterface
{
    /**
     * @return array
     */
    public function getBasketActionBar();

    /**
     * @return string
     */
    public function getPluginLocale();

    /**
     * @return string
     */
    public function getPluginName();
}
