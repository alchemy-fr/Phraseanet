<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

interface InformationInterface
{
    /**
     * The name of the information
     *
     * @return String
     */
    public function getName();

    /**
     * The value of the information
     *
     * @return String
     */
    public function getValue();
}
