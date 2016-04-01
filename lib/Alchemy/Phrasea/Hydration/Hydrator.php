<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Hydration;

interface Hydrator
{
    /**
     * Hydrate an instance with provided data
     *
     * @param object $instance
     * @param array $data
     * @return void
     */
    public function hydrate($instance, array $data);

    /**
     * Extracts data from an instance
     *
     * @param object $instance
     * @return array
     */
    public function extract($instance);
}
