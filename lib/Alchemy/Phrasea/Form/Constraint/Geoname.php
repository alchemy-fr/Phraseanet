<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Form\Constraint;

use Alchemy\Phrasea\Application;
use Symfony\Component\Validator\Constraint;
use Alchemy\Geonames\Connector;
use Alchemy\Geonames\Exception\TransportException;
use Alchemy\Geonames\Exception\NotFoundException;

class Geoname extends Constraint
{
    public $message = 'This place does not seem to exist.';
    private $connector;

    public function __construct(Connector $connector)
    {
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

    public static function create(Application $app)
    {
        return new static($app['geonames.connector']);
    }
}
