<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2010 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     Setup
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Setup_ConstraintsIterator implements IteratorAggregate
{
    protected $constraints = array();

    public function __construct(Array $constraints)
    {
        $this->constraints = $constraints;
    }

    public function add(Setup_Constraint $constraint)
    {
        $this->constraints[] = $constraint;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->constraints);
    }
}
