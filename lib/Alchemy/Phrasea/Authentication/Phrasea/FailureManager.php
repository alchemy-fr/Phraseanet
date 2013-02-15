<?php

namespace Alchemy\Phrasea\Authentication\Phrasea;

use Alchemy\Phrasea\Authentication\Exception\RequireCaptchaException;
use Doctrine\ORM\EntityManager;
use Entities\AuthFailure;
use Neutron\ReCaptcha\ReCaptcha;
use Symfony\Component\HttpFoundation\Request;

class FailureManager
{
    /** @var ReCaptcha */
    private $captcha;
    /** @var EntityManager */
    private $em;

    public function __construct(EntityManager $em, ReCaptcha $captcha)
    {
        $this->captcha = $captcha;
        $this->em = $em;
    }

    public function saveFailure($username, Request $request)
    {
        $failures = $this->em
            ->getRepository('Entities\AuthFailure')
            ->findOldFailures();

        if (0 < count($failures)) {
            $n = 0;
            foreach ($failures as $failure) {
                $this->em->remove($failure);

                if (0 === $n++ % 1000) {
                    $this->em->flush();
                    $this->em->clear();
                }
            }

            $this->em->flush();
            $this->em->clear();
        }

        $failure = new AuthFailure();
        $failure->setIp($request->getClientIp());
        $failure->setUsername($username);
        $failure->setLocked(true);

        $this->em->persist($failure);
        $this->em->flush();

        return $this;
    }

    public function checkFailures($username, Request $request)
    {
        $failures = $this->em
            ->getRepository('Entities\AuthFailure')
            ->findLockedFailuresMatching($username, $request->getClientIp());

        if (0 === count($failures)) {
            return;
        }

        if (9 < count($failures) && $this->captcha->isSetup()) {
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
}
