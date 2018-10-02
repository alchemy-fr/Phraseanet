<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Setup;

interface RequirementInterface
{
    /**
     * Returns whether the requirement is fulfilled.
     *
     * @return Boolean true if fulfilled, otherwise false
     */
    public function isFulfilled();

    /**
     * Returns the message for testing the requirement.
     *
     * @return string The test message
     */
    public function getTestMessage();

    /**
     * Returns the help text for resolving the problem
     *
     * @return string The help text
     */
    public function getHelpText();

    /**
     * Returns the help text formatted in HTML.
     *
     * @return string The HTML help
     */
    public function getHelpHtml();

    /**
     * Returns whether this is only an optional recommendation and not a mandatory requirement.
     *
     * @return Boolean true if optional, false if mandatory
     */
    public function isOptional();
}
