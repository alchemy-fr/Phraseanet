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

interface Provider
{
    /**
     * Returns CORS options for $request.
     *
     * Any valid CORS option will overwrite those of the previous ones.
     * The method must at least return an empty array.
     *
     * All keys of the library configuration are valid:
     * - bool allow_credentials
     * - bool allow_origin
     * - bool allow_headers
     * - bool origin_regex
     * - array allow_methods
     * - array expose_headers
     * - int max_age
     * - array hosts
     *
     * @param Request $request
     *
     * @return array
     */
    public function getOptionsFor(Request $request);
}
