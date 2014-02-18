<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Model\Entities\AuthFailure;
use Neutron\ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;

class FailureManager
{
    /** @var ReCaptcha */
    private $captcha;
    /** @var EntityManager */
    private $em;
    /** @var integer */
    private $trials;

    public function __construct(EntityManager $em, ReCaptcha $captcha, $trials)
    {
        $this->captcha = $captcha;
        $this->em = $em;

        if ($trials < 0) {
            throw new InvalidArgumentException('Trials number must be a positive integer');
        }

        $this->trials = $trials;
    }

    public function getTrials()
    {
        return $this->trials;
    }

    /**
     * Saves an authentication failure
     *
     * @param string  $username
     * @param Request $request
     *
     * @return FailureManager
     */
    public function saveFailure($username, Request $request)
    {
        $this->removeOldFailures();

        $failure = new AuthFailure();
        $failure->setIp($request->getClientIp());
        $failure->setUsername($username);
        $failure->setLocked(true);

        $this->em->persist($failure);
        $this->em->flush();

        return $this;
    }

    /**
     * Checks a request for previous failures
     *
     * @param string  $username
     * @param Request $request
     *
     * @return FailureManager
     *
     * @throws RequireCaptchaException In case a captcha unlock is required
     */
    public function checkFailures($username, Request $request)
    {
        $failures = $this->em
            ->getRepository('Phraseanet:AuthFailure')
            ->findLockedFailuresMatching($username, $request->getClientIp());

        if (0 === count($failures)) {
            return;
        }

        if ($this->trials < count($failures) && $this->captcha->isSetup()) {
            $response = $this->captcha->bind($request);

            if ($response->isValid()) {
                foreach ($failures as $failure) {
                    $failure->setLocked(false);
                }
                $this->em->flush();
            } else {
                throw new RequireCaptchaException('Too much failure, require captcha');
            }
        }

        return $this;
    }

    /**
     * Removes failures older than 2 monthes
     */
    private function removeOldFailures()
    {
        $failures = $this->em
            ->getRepository('Phraseanet:AuthFailure')
            ->findOldFailures('-2 months');

        if (0 < count($failures)) {
            foreach ($failures as $failure) {
                $this->em->remove($failure);
            }
        }
    }
}
