<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

/**
 * @see https://github.com/sensio/SensioDistributionBundle/blob/master/Resources/skeleton/app/SymfonyRequirements.php
 *
 * Represents a single PHP requirement, e.g. an installed extension.
 * It can be a mandatory requirement or an optional recommendation.
 * There is a special subclass, named PhpIniRequirement, to check a php.ini configuration.
 *
 * @author Tobias Schultze <http://tobion.de>
 */
class Requirement implements RequirementInterface
{
    private $fulfilled;
    private $testMessage;
    private $helpText;
    private $helpHtml;
    private $optional;

    /**
     * Constructor that initializes the requirement.
     *
     * @param Boolean     $fulfilled   Whether the requirement is fulfilled
     * @param string      $testMessage The message for testing the requirement
     * @param string      $helpHtml    The help text formatted in HTML for resolving the problem
     * @param string|null $helpText    The help text (when null, it will be inferred from $helpHtml, i.e. stripped from HTML tags)
     * @param Boolean     $optional    Whether this is only an optional recommendation not a mandatory requirement
     */
    public function __construct($fulfilled, $testMessage, $helpHtml, $helpText = null, $optional = false)
    {
        $this->fulfilled = (Boolean) $fulfilled;
        $this->testMessage = (string) $testMessage;
        $this->helpHtml = (string) $helpHtml;
        $this->helpText = null === $helpText ? strip_tags($this->helpHtml) : (string) $helpText;
        $this->optional = (Boolean) $optional;
    }

    /**
     * {@inheritdoc}
     */
    public function isFulfilled()
    {
        return $this->fulfilled;
    }

    /**
     * {@inheritdoc}
     */
    public function getTestMessage()
    {
        return $this->testMessage;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelpText()
    {
        return $this->helpText;
    }

    /**
     * {@inheritdoc}
     */
    public function getHelpHtml()
    {
        return $this->helpHtml;
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return $this->optional;
    }
}
