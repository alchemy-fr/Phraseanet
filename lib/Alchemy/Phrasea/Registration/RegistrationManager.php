<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Registration;

class RegistrationManager
{
    /** @var \appbox */
    private $appbox;

    public function __construct(\appbox $appbox)
    {
        $this->appbox = $appbox;
    }

    /**
     * Tells whether registration is enabled or not.
     *
     * @return boolean
     */
    public function isRegistrationEnabled()
    {
        foreach ($this->appbox->get_databoxes() as $databox) {
            foreach($databox->get_collections() as $collection) {
                if ($collection->isRegistrationEnabled()) {
                    return true;
                }
            }
        }
        return false;
    }
}
