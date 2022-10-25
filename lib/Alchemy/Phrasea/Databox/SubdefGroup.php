<?php
/**
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Databox;

use Alchemy\Phrasea\Media\Type\Type;

class SubdefGroup implements \IteratorAggregate, \Countable
{
    private $name;

    /**
     * @var Type
     */
    private $type;

    private $isDocumentOrderable;
    private $writemetaOriginalDocument;

    /**
     * @var \databox_subdef[]
     */
    private $subdefs = [];

    /**
     * @param string $name
     * @param Type $type
     * @param bool $isDocumentOrderable
     */
    public function __construct($name, Type $type, $isDocumentOrderable, $writemetaOriginalDocument = true)
    {
        $this->name = $name;
        $this->type = $type;
        $this->isDocumentOrderable = $isDocumentOrderable;
        $this->writemetaOriginalDocument = $writemetaOriginalDocument;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return Type
     */
    public function getType()
    {
        return $this->type;
    }

    public function allowDocumentOrdering()
    {
        $this->isDocumentOrderable = true;
    }

    public function disallowDocumentOrdering()
    {
        $this->isDocumentOrderable = false;
    }

    /**
     * @return bool
     */
    public function isDocumentOrderable()
    {
        return $this->isDocumentOrderable;
    }

    /**
     * @return bool
     */
    public function toWritemetaOriginalDocument()
    {
        return $this->writemetaOriginalDocument;
    }

    public function addSubdef(\databox_subdef $subdef)
    {
        $this->subdefs[$subdef->get_name()] = $subdef;
    }

    /**
     * @param string|\databox_subdef $subdef
     * @return bool
     */
    public function hasSubdef($subdef)
    {
        $subdefName = $subdef instanceof \databox_subdef ? $subdef->get_name() : $subdef;

        return isset($this->subdefs[$subdefName]);
    }

    /**
     * @param string $subdefName
     * @return \databox_subdef
     */
    public function getSubdef($subdefName)
    {
        if (!isset($this->subdefs[$subdefName])) {
            throw new \RuntimeException('Requested subdef was not found');
        }

        return $this->subdefs[$subdefName];
    }

    /**
     * @param string|\databox_subdef $subdef
     */
    public function removeSubdef($subdef)
    {
        $subdefName = $subdef instanceof \databox_subdef ? $subdef->get_name() : $subdef;

        unset($this->subdefs[$subdefName]);
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->subdefs);
    }

    public function count()
    {
        return count($this->subdefs);
    }
}
