<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Model\Manipulator;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Model\Entities\ApiApplication;
use Alchemy\Phrasea\Model\Entities\User;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use RandomLib\Generator;

class ApiApplicationManipulator implements ManipulatorInterface
{
    private $om;
    private $repository;
    private $randomGenerator;

    public function __construct(ObjectManager $om, EntityRepository $repo, Generator $random)
    {
        $this->om = $om;
        $this->repository = $repo;
        $this->randomGenerator = $random;
    }

    public function create($name, $type, $description, $applicationWebsite, User $creator = null, $redirectUri = null)
    {
        $application = new ApiApplication();
        $application->setCreator($creator);
        $application->setName($name);
        $application->setDescription($description);
        $this->doSetType($application, $type);
        $this->doSetWebsiteUrl($application, $applicationWebsite);
        $this->doSetRedirectUri($application, $redirectUri);
        $application->setNonce($this->randomGenerator->generateString(64));
        $application->setClientId($this->randomGenerator->generateString(32, TokenManipulator::LETTERS_AND_NUMBERS));
        $application->setClientSecret($this->randomGenerator->generateString(32, TokenManipulator::LETTERS_AND_NUMBERS));

        $this->om->persist($application);
        $this->om->flush();

        return $application;
    }

    public function delete(ApiApplication $application)
    {
        $this->om->remove($application);
        $this->om->flush();
    }

    public function deleteApiApplications(array $applications)
    {
        foreach ($applications as $application) {
            $this->om->remove($application);
        }
        $this->om->flush();
    }

    public function update(ApiApplication $application)
    {
        $this->om->persist($application);
        $this->om->flush();
    }

    public function setType(ApiApplication $application, $type)
    {
        $this->doSetType($application, $type);
        $this->update($application);
    }

    public function setRedirectUri(ApiApplication $application, $uri)
    {
        $this->doSetRedirectUri($application, $uri);
        $this->update($application);
    }

    public function setWebsiteUrl(ApiApplication $application, $url)
    {
        $this->doSetWebsiteUrl($application, $url);
        $this->update($application);
    }

    public function setWebhookUrl(ApiApplication $application, $url)
    {
        // by default activate webhook when providing webhook_url
        $application->setWebhookActive(true);

        $this->doSetWebhookUrl($application, $url);
        $this->update($application);
    }

    private function doSetType(ApiApplication $application, $type)
    {
        if (!in_array($type, [ApiApplication::DESKTOP_TYPE, ApiApplication::WEB_TYPE])) {
            throw new InvalidArgumentException(sprintf('%s api application type is not supported, it should be one of the following %s', $type, implode(', ', [ApiApplication::DESKTOP_TYPE, ApiApplication::WEB_TYPE])));
        }
        $application->setType($type);
    }

    private function doSetRedirectUri(ApiApplication $application, $uri)
    {
        if ($application->getType() === ApiApplication::DESKTOP_TYPE) {
            $application->setRedirectUri(ApiApplication::NATIVE_APP_REDIRECT_URI);

            return;
        }

        if (false === filter_var($uri, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
            throw new InvalidArgumentException(sprintf('Redirect Uri Url %s is not legal.', $uri));
        }

        $application->setRedirectUri($uri);
    }

    private function doSetWebsiteUrl(ApiApplication $application, $url)
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
            throw new InvalidArgumentException(sprintf('Website Url %s is not legal.', $url));
        }

        $application->setWebsite($url);
    }

    private function doSetWebhookUrl(ApiApplication $application, $url)
    {
        if (false === filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED | FILTER_FLAG_HOST_REQUIRED)) {
            throw new InvalidArgumentException(sprintf('Webhook Url %s is not legal.', $url));
        }

        $application->setWebhookUrl($url);
    }
}
