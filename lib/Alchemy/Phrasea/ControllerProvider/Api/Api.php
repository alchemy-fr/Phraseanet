<?php

namespace Alchemy\Phrasea\ControllerProvider\Api;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Silex\Application;

abstract class Api
{

    protected function isApiEnabled(Application $application)
    {
        /** @var PropertyAccess $config */
        $config = $application['conf'];

        return $config->get([ 'registry', 'api-clients', 'api-enabled' ], true) == true;
    }
}
