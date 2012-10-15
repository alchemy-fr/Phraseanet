<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 *
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
class Export implements ControllerProviderInterface
{

    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $controllers = $app['controllers_factory'];

        $controllers->before(function(Request $request) use ($app) {
                $app['firewall']->requireNotGuest();
            });

        /**
         * Display multi export
         *
         * name         : export_multi_export
         *
         * description  : Display multi export
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : HTML Response
         */
        $controllers->post('/multi-export/', $this->call('displayMultiExport'))
            ->bind('export_multi_export');

        /**
         * Export by mail
         *
         * name         : export_mail
         *
         * description  : Export by mail
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/mail/', $this->call('exportMail'))
            ->bind('export_mail');

        /**
         * Export by FTP
         *
         * name         : export_ftp
         *
         * description  : Export by FTP
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/ftp/', $this->call('exportFtp'))
            ->bind('export_ftp');

        /**
         * Test FTP connexion
         *
         * name         : export_ftp_test
         *
         * description  : Test a FTP connexion
         *
         * method       : POST
         *
         * parameters   : none
         *
         * return       : JSON Response
         */
        $controllers->post('/ftp/test/', $this->call('testFtpConnexion'))
            ->bind('export_ftp_test');

        return $controllers;
    }

    /**
     * Display form to export documents
     *
     * @param   Application $app
     * @param   Request     $request
     * @return  Response
     */
    public function displayMultiExport(Application $app, Request $request)
    {
        $download = new \set_export($app, $request->request->get('lst', ''), (int) $request->request->get('ssel'), $request->request->get('story'));

        return new Response($app['twig']->render('common/dialog_export.html.twig', array(
                    'download'             => $download,
                    'ssttid'               => $request->request->get('ssel'),
                    'lst'                  => $download->serialize_list(),
                    'default_export_title' => $app['phraseanet.registry']->get('GV_default_export_title'),
                    'choose_export_title'  => $app['phraseanet.registry']->get('GV_choose_export_title')
                )));
    }

    /**
     * Test a FTP connexion
     *
     * @param   Application     $app
     * @param   Request         $request
     * @return  JsonResponse
     */
    public function testFtpConnexion(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $success = false;
        try {
            $ftpClient = $app['phraseanet.ftp.client']($request->request->get('addr', ''), 21, 90, !!$request->request->get('ssl'));
            $ftpClient->login($request->request->get('login', ''), $request->request->get('pwd', ''));
            $ftpClient->close();
            $msg = _('Connection to FTP succeed');
            $success = true;
        } catch (Exception $e) {
            $msg = sprintf(_('Error while connecting to FTP'));
        }

        return $app->json(array(
                'success' => $success,
                'message' => $msg
            ));
    }

    /**
     *
     * @param   Application   $app
     * @param   Request       $request
     * @return  JsonResponse
     */
    public function exportFtp(Application $app, Request $request)
    {
        $download = new \set_exportftp($app, $request->request->get('lst'), $request->request->get('ssttid'));

        if (null === $address = $request->request->get('addr')) {
            $app->abort(400, _('Missing ftp address'));
        }

        if (null === $login = $request->request->get('login')) {
            $app->abort(400, _('Missing ftp lofin'));
        }

        if (null === $destFolder = $request->request->get('destfolder')) {
            $app->abort(400, _('Missing destination folder'));
        }

        if (null === $folderTocreate = $request->request->get('NAMMKDFOLD')) {
            $app->abort(400, _('Missing folder to create'));
        }

        if (null === $subdefs = $request->request->get('obj')) {
            $app->abort(400, _('Missing subdefs to export'));
        }

        if (count($download->get_display_ftp()) == 0) {

            return $app->json(array('success' => false, 'message' => _('Documents can be sent by FTP')));
        } else {
            try {
                $download->prepare_export($app['phraseanet.user'], $app['filesystem'], $request->request->get('obj'), false, $request->request->get('businessfields'));
                $download->export_ftp($request->request->get('user_dest'), $address, $login, $request->request->get('pwd', ''), $request->request->get('ssl'), $request->request->get('nbretry'), $request->request->get('passif'), $destFolder, $folderTocreate, $request->request->get('logfile'));

                return $app->json(array(
                        'success' => true,
                        'message' => _('Export saved in the waiting queue')
                    ));
            } catch (Exception $e) {

                return $app->json(array(
                        'success' => false,
                        'message' => _('Something gone wrong')
                    ));
            }
        }
    }

    public function exportMail(Application $app, Request $request)
    {
        set_time_limit(0);
        session_write_close();
        ignore_user_abort(true);

        //prepare export
        $download = new \set_export($app, $request->get('lst', ''), $request->get('ssttid', ''));
        $list = $download->prepare_export($app['phraseanet.user'], $app['filesystem'], $request->get('obj'), $request->get("type") == "title" ? : false, $request->get('businessfields'));
        $list['export_name'] = sprintf("%s.zip", $download->getExportName());
        $list['email'] = $request->get("destmail", "");

        $destMails = array();
        //get destination mails
        foreach (explode(";", $list['email']) as $mail) {
            if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $destMails[] = $mail;
            } else {
                $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', array(
                    'usr_id' => $app['phraseanet.user']->get_id(),
                    'lst'    => $request->get('lst', ''),
                    'ssttid' => $request->get('ssttid', ''),
                    'dest'   => $mail,
                    'reason' => \eventsmanager_notify_downloadmailfail::MAIL_NO_VALID
                ));
            }
        }

        //generate validation token
        $endDateObject = new \DateTime('+1 day');
        $token = \random::getUrlToken($app, \random::TYPE_EMAIL, false, $endDateObject, serialize($list));

        if (count($destMails) > 0 && $token) {
            //zip documents
            \set_export::build_zip(new Filesystem(), $token, $list, $app['phraseanet.registry']->get('GV_RootPath') . 'tmp/download/' . $token . '.zip');

            $remaingEmails = $destMails;

            $url = $app['phraseanet.registry']->get('GV_ServerName') . 'mail-export/' . $token . '/';

            $from = array(
                'name'  => $app['phraseanet.user']->get_display_name(),
                'email' => $app['phraseanet.user']->get_email()
            );

            //send mails
            foreach ($destMails as $key => $mail) {
                if (\mail::send_documents($app, trim($mail), $url, $from, $endDateObject, $request->get('textmail'), $request->get('reading_confirm') == '1' ? : false)) {
                    unset($remaingEmails[$key]);
                }
            }

            //some mails failed
            if (count($remaingEmails) > 0) {
                foreach ($remaingEmails as $mail) {
                    $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', array(
                        'usr_id' => $app['phraseanet.user']->get_id(),
                        'lst'    => $request->get('lst', ''),
                        'ssttid' => $request->get('ssttid', ''),
                        'dest'   => $mail,
                        'reason' => \eventsmanager_notify_downloadmailfail::MAIL_FAIL
                    ));
                }
            }
        } elseif (!$token && count($destMails) > 0) { //couldn't generate token
            foreach ($destMails as $mail) {
                $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', array(
                    'usr_id' => $app['phraseanet.user']->get_id(),
                    'lst'    => $request->get('lst', ''),
                    'ssttid' => $request->get('ssttid', ''),
                    'dest'   => $mail,
                    'reason' => 0
                ));
            }
        }
    }

    /**
     * Prefix the method to call with the controller class name
     *
     * @param  string $method The method to call
     * @return string
     */
    private function call($method)
    {
        return sprintf('%s::%s', __CLASS__, $method);
    }
}
