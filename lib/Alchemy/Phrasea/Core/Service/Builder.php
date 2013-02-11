<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Core\Service;

use Alchemy\Phrasea\Application;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Builder
{

    public static function create(Application $app, ParameterBag $configuration)
    {
        $classname = __NAMESPACE__ . '\\' . $configuration->get("type");

        if ( ! class_exists($classname)) {
            throw new Exception\ServiceNotFound(sprintf('Service %s not found', $classname));
        }

        try {
            $options = $configuration->get("options") ?: array() ;
        } catch (\Exception $e) {
            $options = array();
        }

        return new $classname($app, $options);
    }
}
