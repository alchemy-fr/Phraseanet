<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Notification;

interface EmitterInterface
{
    /**
     * Returns the Emitter's name
     *
     * @return string|null
     */
    public function getName();

    /**
     * Returns the Emitter's email
     *
     * @return string|null
     */
    public function getEmail();
}
