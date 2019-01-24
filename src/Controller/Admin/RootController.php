<?php

namespace App\Controller\Admin;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class RootController extends Controller
{

    public function indexAction(Request $request)
    {
//        try {
//            \Session_Logger::updateClientInfos($this->app, 3);
//        } catch (SessionNotFound $e) {
//            return $this->app->redirectPath('logout');
//        }

        $params = $this->getSectionParameters($request->query->get('section', false));

        return $this->render('admin/index.html.twig', array_merge([
            'module'        => 'admin',
            'events'        => $this->get('events.manager')->start(),
            'module_name'   => 'Admin',
            'notice'        => $request->query->get("notice")
        ], $params));
    }

    /**
     * @param string $section
     * @return array
     */
    private function getSectionParameters($section)
    {
        $available = [
            'connected',
            'registrations',
            'taskmanager',
            'base',
            'bases',
            'collection',
            'user',
            'users',
        ];

        $feature = 'connected';
        $featured = false;
        $position = explode(':', $section);
        if (count($position) > 0) {
            if (in_array($position[0], $available)) {
                $feature = $position[0];

                if (isset($position[1])) {
                    $featured = $position[1];
                }
            }
        }

        $databoxes = $off_databoxes = [];
//        $acl = $this->getAclForUser();
//        foreach ($this->getApplicationBox()->get_databoxes() as $databox) {
//            try {
//                if (!$acl->has_access_to_sbas($databox->get_sbas_id())) {
//                    continue;
//                }
//                $databox->get_connection();
//            } catch (\Exception $e) {
//                $off_databoxes[] = $databox;
//                continue;
//            }
//
//            $databoxes[] = $databox;
//        }

        return [
            'feature'       => $feature,
            'featured'      => $featured,
            'databoxes'     => $databoxes,
            'off_databoxes' => $off_databoxes,
        ];
    }


}