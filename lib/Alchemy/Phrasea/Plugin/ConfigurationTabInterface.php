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

interface ConfigurationTabInterface
{
    /**
     * Get the title translation key in plugin domain
     *
     * @return string
     */
    public function getTitle();

    /**
     * Get the url where configuration tab can be retrieved
     *
     * @return string
     */
    public function getUrl();
}
