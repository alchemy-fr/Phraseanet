<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Alchemy\Phrasea\Model\Repositories\AuthFailureRepository;
use Doctrine\ORM\EntityManager;
use Alchemy\Phrasea\Model\Entities\AuthFailure;
use ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;

class FailureManager
{
    /**
     * @var ReCaptcha
     */
    private $captcha;

    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var AuthFailureRepository
     */
    private $repository;

    /**
     * @var int
     */
    private $trials;

    public function __construct(AuthFailureRepository $repo, EntityManager $em, ReCaptcha $captcha, $trials)
    {
        $this->captcha = $captcha;
        $this->em = $em;
        $this->repository = $repo;

        if ($trials < 0) {
            throw new InvalidArgumentException('Trials number must be a positive integer');
        }

        $this->trials = (int)$trials;
    }

    /**
     * @return int
     */
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
        $failures = $this->repository->findLockedFailuresMatching($username, $request->getClientIp());

        if (0 === count($failures)) {
            return $this;
        }

        if ($this->trials < count($failures)) {

            $captchaResp = "";

            if (isset($_POST["g-recaptcha-response"])) {
                $captchaResp = $_POST["g-recaptcha-response"];
            }

            $response = $this->captcha->verify($captchaResp, $request->getClientIp());

            if (!$response->isSuccess()) {
                throw new RequireCaptchaException('Too many failures, require captcha');
            }

            foreach ($failures as $failure) {
                $failure->setLocked(false);
            }

            $this->em->flush($failures);
        }

        return $this;
    }

    private function removeOldFailures()
    {
        $failures = $this->repository->findOldFailures('-2 months');

        if (empty($failures)) {
            return;
        }

        foreach ($failures as $failure) {
            $this->em->remove($failure);
        }

        $this->em->flush($failures);
    }
}
