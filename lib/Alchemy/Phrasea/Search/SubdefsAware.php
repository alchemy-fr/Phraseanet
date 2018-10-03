<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Search;

use Assert\Assertion;

trait SubdefsAware
{
    /**
     * @var SubdefView[]
     */
    private $subdefs = [];

    /**
     * @param SubdefView[] $subdefs
     */
    public function setSubdefs($subdefs)
    {
        Assertion::allIsInstanceOf($subdefs, SubdefView::class);

        $this->subdefs = [];

        foreach ($subdefs as $subdef) {
            $this->subdefs[$subdef->getSubdef()->get_name()] = $subdef;
        }
    }

    /**
     * @param string $name
     * @return SubdefView
     */
    public function getSubdef($name)
    {
        if (isset($this->subdefs[$name])) {
            return $this->subdefs[$name];
        }

        throw new \OutOfBoundsException(sprintf('There are no subdef named "%s"', $name));
    }

    /**
     * @return SubdefView
     */
    public function getSubdefs()
    {
        return $this->subdefs;
    }
}
