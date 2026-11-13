<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2015 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Cors\Options;

use Symfony\Component\HttpFoundation\Request;

interface Resolver
{
    /**
     * Returns CORS options for given Request
     *
     * @param Request $request
     *
     * @return array
     */
    public function getOptionsFor(Request $request);
}
