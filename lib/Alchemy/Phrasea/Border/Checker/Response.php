<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border\Checker;

use Symfony\Component\Translation\TranslatorInterface;

/**
 * The response of a check
 */
class Response
{
    protected $ok;
    protected $checker;

    /**
     * Constructor
     *
     * @param boolean          $ok      True if the response is OK
     * @param CheckerInterface $checker The checker attachedto the response
     */
    public function __construct($ok, CheckerInterface $checker)
    {
        $this->ok = $ok;
        $this->checker = $checker;
    }

    /**
     * Destructor
     */
    public function __destruct()
    {
        $this->checker = null;
    }

    /**
     * Returns true if the response is OK
     *
     * @return boolean
     */
    public function isOk()
    {
        return $this->ok;
    }

    /**
     * Returns the message attached to the check, in case the response is bad
     *
     * @return string
     */
    public function getMessage(TranslatorInterface $translator)
    {
        return $this->checker->getMessage($translator);
    }

    /**
     * Returns the attached Checker
     *
     * @return CheckerInterface
     */
    public function getChecker()
    {
        return $this->checker;
    }
}
