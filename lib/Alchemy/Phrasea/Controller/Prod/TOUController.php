<?php
/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Alchemy\Phrasea\Controller\Prod;

use Alchemy\Phrasea\Application;
use Alchemy\Phrasea\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class TOUController extends Controller
{
    /**
     * Deny database terms of use
     *
     * @param  integer      $sbas_id
     * @return Response
     */
    public function denyTermsOfUse($sbas_id)
    {
        try {
            $databox = $this->findDataboxById((int) $sbas_id);

            $this->getAclForUser()->revoke_access_from_bases(
                array_keys($this->getAclForUser()->get_granted_base([], [$databox->get_sbas_id()]))
            );
            $this->getAclForUser()->revoke_unused_sbas_rights();

            $this->getAuthenticator()->closeAccount();

            $ret = ['success' => true, 'message' => ''];
        } catch (\Exception $exception) {
            $ret = ['success' => false, 'message' => $exception->getMessage()];
        }

        return $this->app->json($ret);
    }

    /**
     * Display database terms of use
     *
     * @param  Request     $request
     * @return Response
     */
    public function displayTermsOfUse(Request $request)
    {
        $toDisplay = $request->query->get('to_display', []);
        $data = [];

        foreach ($this->getApplicationBox()->get_databoxes() as $databox) {
            if (count($toDisplay) > 0 && !in_array($databox->get_sbas_id(), $toDisplay)) {
                continue;
            }

            $cgus = $databox->get_cgus();

            if (!isset($cgus[$this->app['locale']])) {
                continue;
            }

            $data[$databox->get_label($this->app['locale'])] = $cgus[$this->app['locale']]['value'];
        }

        return $this->renderResponse('/prod/TOU.html.twig', [
            'TOUs'        => $data,
            'local_title' => $this->app->trans('Terms of use'),
        ]);
    }
}
