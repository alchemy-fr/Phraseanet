<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2018 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Border;

use Alchemy\Phrasea\Border\Checker\Response;

/**
 * The Phraseanet Border Visa
 *
 * When a file is submitted to Phraseanet, it is checked against constraints and
 * a visa is returned.
 *
 * This Visa provides an interface to get Checkers (constraints) responses and
 * to know is a submitted file package is a valid candidate for a new record
 *
 */
class Visa
{
    protected $responses;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        $this->responses = [];
    }

    /**
     * Destructor
     *
     */
    public function __destruct()
    {
        $this->responses = null;
    }

    /**
     * Add a checker Response to the visa
     *
     * @param  Response $response A Checker Response
     * @return Visa
     */
    public function addResponse(Response $response)
    {
        array_push($this->responses, $response);

        return $this;
    }

    /**
     * Get all the responses generated by the Checkers
     *
     * @return Response[] An array of Response
     */
    public function getResponses()
    {
        return $this->responses;
    }

    /**
     * Return true if all Checkers are ok for the File package candidate
     *
     * @return boolean
     */
    public function isValid()
    {
        foreach ($this->responses as $response) {

            if (!$response->isOk()) {
                return false;
            }
        }

        return true;
    }
}
