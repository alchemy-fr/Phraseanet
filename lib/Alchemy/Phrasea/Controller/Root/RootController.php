<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Root;

use Alchemy\Phrasea\Application\Helper\EntityManagerAware;
use Alchemy\Phrasea\Controller\Controller;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class RootController extends Controller
{
    use EntityManagerAware;

    public function getRobots()
    {
        if ($this->getConf()->get(['registry', 'general', 'allow-indexation']) === true) {
            $buffer = "User-Agent: *\n" . "Allow: /\n";
        } else {
            $buffer = "User-Agent: *\n" . "Disallow: /\n";
        }

        return new Response($buffer, 200, ['Content-Type' => 'text/plain']);
    }

    public function getRoot()
    {
        return $this->app->redirectPath('homepage');
    }

    public function setLocale($locale)
    {
        $response = $this->app->redirectPath('root');
        $response->headers->setCookie(new Cookie('locale', $locale));

        $authenticatedUser = $this->getAuthenticatedUser();

        // if connected, update user locale
        if ($authenticatedUser !== null) {
            try {
                $authenticatedUser->setLocale($locale);

                $this->getEntityManager()->flush();
            } catch (\Exception $e) {
                // invalid locale
            }
        }

        return $response;
    }

    public function getAvailableLanguages()
    {
        return $this->app->json($this->app['locales.available']);
    }
}
