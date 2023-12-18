<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Admin;

use Alchemy\Geonames\Exception\ExceptionInterface;
use Alchemy\Geonames\Geoname;
use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Model\Entities\Session;
use Alchemy\Phrasea\Model\Repositories\SessionRepository;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

class ConnectedUsersController extends Controller
{
    /** @var TranslatorInterface */
    private $translator;
    protected $moduleNames;
    /** @var LoggerInterface */
    protected $logger;

    public function __construct(Application $app)
    {
        parent::__construct($app);
        $this->translator = $app['translator'];
        $this->logger = $app['monolog'];
    }

    public function listConnectedUsers(Request $request)
    {
        $dql = 'SELECT s FROM Phraseanet:Session s WHERE s.updated > :date ORDER BY s.updated DESC';
        $date = new \DateTime('-2 hours');

        /** @var EntityManager $manager */
        $manager = $this->app['orm.em'];

        $query = $manager->createQuery($dql);
        $query->setParameter('date', $date->format('Y-m-d h:i:s'));
        /** @var Session[] $sessions */
        $sessions = $query->getResult();

        $result = [];

        foreach ($sessions as $session) {
            $info = '';
            try {
                /** @var Geoname $geoname */
                $geoname = $this->app['geonames.connector']->ip($session->getIpAddress());
                $country = $geoname->get('country');
                $city = $geoname->get('city');
                $region = $geoname->get('region');

                $countryName = isset($country['name']) ? $country['name'] : null;
                $regionName = isset($region['name']) ? $region['name'] : null;

                if (null !== $city) {
                    $info = $city . ($countryName ? ' (' . $countryName . ')' : null);
                } elseif (null !== $regionName) {
                    $info = $regionName . ($countryName ? ' (' . $countryName . ')' : null);
                } elseif (null !== $countryName) {
                    $info = $countryName;
                } else {
                    $info = '';
                }
            } catch (ExceptionInterface $e) {
//                $this->logger->error(
//                    sprintf("Unable to get IP information for %s", $session->getIpAddress()),
//                    ['exception' => $e]
//                );
            }

            $result[] = [
                'session' => $session,
                'info' => $info,
            ];
        }

        $ret = [
            'sessions'     => $result,
            'applications' => array_fill(0, 9, 0),
        ];

        foreach ($result as $session) {
            foreach ($session['session']->getModules() as $module) {
                if (isset($ret['applications'][$module->getModuleId()])) {
                    $ret['applications'][$module->getModuleId()]++;
                }
            }
        }

        return $this->app['twig']->render('admin/connected-users.html.twig', ['data' => $ret]);
    }

    /**
     * Return module name according to its ID
     *
     * @param  integer $appId
     * @return string
     */
    public function getModuleNameFromId($appId)
    {
        if (null === $this->moduleNames) {
            $translator = $this->translator;
            $this->moduleNames = [
                '0' => $translator->trans('admin::monitor: module inconnu'),
                '1' => $translator->trans('admin::monitor: module production'),
                '2' => $translator->trans('admin::monitor: module client'),
                '3' => $translator->trans('admin::monitor: module admin'),
                '4' => $translator->trans('admin::monitor: module report'),
                '5' => $translator->trans('admin::monitor: module thesaurus'),
                '6' => $translator->trans('admin::monitor: module comparateur'),
                '7' => $translator->trans('admin::monitor: module validation'),
                '8' => $translator->trans('admin::monitor: module upload'),
            ];
        }

        return isset($this->moduleNames[$appId]) ? $this->moduleNames[$appId] : null;
    }

    public function disconnectSessionId(Request $request, $session_id)
    {
        /** @var EntityManager $manager */
        $manager = $this->app['orm.em'];

        /** @var SessionRepository $repoSessions */
        $repoSessions = $this->app['repo.sessions'];

        $session = $repoSessions->find($session_id);

        if ($session != null) {
            $manager->remove($session);
            $manager->flush();
        }

        return $this->app->redirectPath('admin_connected_users');
    }
}
