<?php

/*
 * This file is part of Geonames API Connector.
 *
 * (c) Alchemy <dev.team@alchemy.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Geonames;

class Geoname
{
    private $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function __toString()
    {
        return json_encode($this->data);
    }

    /**
     * Returns the value of a given property.
     *
     * @param string $property
     *
     * @return mixed|null
     */
    public function get($property)
    {
        return isset($this->data[$property]) ? $this->data[$property] : null;
    }

    /**
     * Returns true if the property exists.
     *
     * @param string $property
     *
     * @return boolean
     */
    public function has($property)
    {
        return isset($this->data[$property]);
    }
}
