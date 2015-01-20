<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Constraint;

use Symfony\Component\Validator\Constraint;
use Alchemy\Geonames\Connector;
use Alchemy\Geonames\Exception\TransportException;
use Alchemy\Geonames\Exception\NotFoundException;

class Geoname extends Constraint
{
    private $connector;
    private $message;

    public function __construct(Connector $connector)
    {
        $this->message = _('This place does not seem to exist.');
        $this->connector = $connector;
        parent::__construct();
    }

    public function isValid($geonameid)
    {
        try {
            $this->connector->geoname($geonameid);
        } catch (TransportException $e) {
            return true;
        } catch (NotFoundException $e) {
            return false;
        }

        return true;
    }
}
