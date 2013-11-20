<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2013 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Vocabulary;

class Term
{
    /**
     *
     * @var string
     */
    protected $value;

    /**
     *
     * @var string
     */
    protected $context;

    /**
     *
     * @var ControlProviderInterface
     */
    protected $type;

    /**
     *
     * @var mixed
     */
    protected $id;

    /**
     * Construct a Term
     *
     * @param string                   $value   the scalar value of the Term
     * @param string                   $context A string defining the context of the Term
     * @param ControlProviderInterface $type    A Vocabulary Controller
     * @param mixed                    $id      The id of the term in the Vocabulary Controller
     *
     * @return \Alchemy\Phrasea\Vocabulary\ControlProvider\Term
     * @throws Exception
     */
    public function __construct($value, $context = null, ControlProvider\ControlProviderInterface $type = null, $id = null)
    {

        if ( ! assert(is_scalar($value))) {
            throw new \Exception('A Term value should be scalar');
        }

        $this->value = $value;
        $this->context = $context;
        $this->type = $type;
        $this->id = $id;

        return $this;
    }

    /**
     * Get the scalar value of a term
     *
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Get the content of a term
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     *
     * @return ControlProviderInterface
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @return mixed
     */
    public function getId()
    {
        return $this->id;
    }
}
