<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2014 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Controller\Prod;

use Silex\Application;
use Silex\ControllerProviderInterface;
use Alchemy\Phrasea\Exception\InvalidArgumentException;
use Alchemy\Phrasea\Notification\Emitter;
use Alchemy\Phrasea\Notification\Receiver;
use Alchemy\Phrasea\Notification\Mail\MailRecordsExport;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class Export implements ControllerProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function connect(Application $app)
    {
        $app['controller.prod.export'] = $this;

        $controllers = $app['controllers_factory'];

        $controllers->before(function (Request $request) use ($app) {
            $app['firewall']->requireNotGuest();
        });

        $controllers->post('/multi-export/', 'controller.prod.export:displayMultiExport')
            ->bind('export_multi_export');

        $controllers->post('/mail/', 'controller.prod.export:exportMail')
            ->bind('export_mail');

        $controllers->post('/ftp/', 'controller.prod.export:exportFtp')
            ->bind('export_ftp');

        $controllers->post('/ftp/test/', 'controller.prod.export:testFtpConnexion')
            ->bind('export_ftp_test');

        return $controllers;
    }

    /**
     * Display form to export documents
     *
     * @param  Application $app
     * @param  Request     $request
     * @return Response
     */
    public function displayMultiExport(Application $app, Request $request)
    {
        $download = new \set_export(
            $app,
            $request->request->get('lst', ''),
            $request->request->get('ssel', ''),
            $request->request->get('story')
        );

        return new Response($app['twig']->render('common/dialog_export.html.twig', [
            'download'             => $download,
            'ssttid'               => $request->request->get('ssel'),
            'lst'                  => $download->serialize_list(),
            'default_export_title' => $app['conf']->get(['registry', 'actions', 'default-export-title']),
            'choose_export_title'  => $app['conf']->get(['registry', 'actions', 'export-title-choice'])
        ]));
    }

    /**
     * Test a FTP connexion
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function testFtpConnexion(Application $app, Request $request)
    {
        if (!$request->isXmlHttpRequest()) {
            $app->abort(400);
        }

        $success = false;
        try {
            $ftpClient = $app['phraseanet.ftp.client']($request->request->get('address', ''), 21, 90, !!$request->request->get('ssl'));
            $ftpClient->login($request->request->get('login', 'anonymous'), $request->request->get('password', 'anonymous'));
            $ftpClient->close();
            $msg = $app->trans('Connection to FTP succeed');
            $success = true;
        } catch (\Exception $e) {
            $msg = $app->trans('Error while connecting to FTP');
        }

        return $app->json([
            'success' => $success,
            'message' => $msg
        ]);
    }

    /**
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function exportFtp(Application $app, Request $request)
    {
        $download = new \set_exportftp($app, $request->request->get('lst'), $request->request->get('ssttid'));

        $mandatoryParameters = ['address', 'login', 'obj'];

        foreach ($mandatoryParameters as $parameter) {
            if (!$request->request->get($parameter)) {
                $app->abort(400, sprintf('required parameter `%s` is missing', $parameter));
            }
        }

        if (count($download->get_display_ftp()) == 0) {
            return $app->json([
                'success' => false,
                'message' => $app->trans("You do not have required rights to send these documents over FTP")
            ]);
        }

        try {
            $download->prepare_export(
                $app['authentication']->getUser(),
                $app['filesystem'],
                $request->request->get('obj'),
                false,
                $request->request->get('businessfields')
            );

            $download->export_ftp(
                $request->request->get('user_dest'),
                $request->request->get('address'),
                $request->request->get('login'),
                $request->request->get('password', ''),
                $request->request->get('ssl'),
                $request->request->get('max_retry'),
                $request->request->get('passive'),
                $request->request->get('dest_folder'),
                $request->request->get('prefix_folder'),
                $request->request->get('logfile')
            );

            return $app->json([
                'success' => true,
                'message' => $app->trans('Export saved in the waiting queue')
            ]);
        } catch (\Exception $e) {
            return $app->json([
                'success' => false,
                'message' => $app->trans('Something went wrong')
            ]);
        }
    }

    /**
     * Export document by mail
     *
     * @param  Application  $app
     * @param  Request      $request
     * @return JsonResponse
     */
    public function exportMail(Application $app, Request $request)
    {
        set_time_limit(0);
        session_write_close();
        ignore_user_abort(true);

        $lst = $request->request->get('lst', '');
        $ssttid = $request->request->get('ssttid', '');

        //prepare export
        $download = new \set_export($app, $lst, $ssttid);
        $list = $download->prepare_export(
            $app['authentication']->getUser(),
            $app['filesystem'],
            (array) $request->request->get('obj'),
            $request->request->get("type") == "title" ? : false,
            $request->request->get('businessfields')
        );

        $separator = preg_split('//', ' ;,', -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        $separator = '/\\' . implode('|\\', $separator) . '/';

        $list['export_name'] = sprintf("%s.zip", $download->getExportName());
        $list['email'] = implode(';', preg_split($separator, $request->request->get("destmail", "")));

        $destMails = [];
        //get destination mails
        foreach (explode(";", $list['email']) as $mail) {
            if (filter_var($mail, FILTER_VALIDATE_EMAIL)) {
                $destMails[] = $mail;
            } else {
                $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', [
                    'usr_id' => $app['authentication']->getUser()->get_id(),
                    'lst'    => $lst,
                    'ssttid' => $ssttid,
                    'dest'   => $mail,
                    'reason' => \eventsmanager_notify_downloadmailfail::MAIL_NO_VALID
                ]);
            }
        }

        //generate validation token
        $endDateObject = new \DateTime('+1 day');
        $token = $app['tokens']->getUrlToken(\random::TYPE_EMAIL, false, $endDateObject, serialize($list));

        if (count($destMails) > 0 && $token) {
            //zip documents
            \set_export::build_zip(
                $app,
                $token,
                $list,
                $app['root.path'] . '/tmp/download/' . $token . '.zip'
            );

            $remaingEmails = $destMails;

            $url = $app->url('prepare_download', ['token' => $token, 'anonymous']);

            $emitter = new Emitter($app['authentication']->getUser()->get_display_name(), $app['authentication']->getUser()->get_email());

            foreach ($destMails as $key => $mail) {
                try {
                    $receiver = new Receiver(null, trim($mail));
                } catch (InvalidArgumentException $e) {
                    continue;
                }

                $mail = MailRecordsExport::create($app, $receiver, $emitter, $request->request->get('textmail'));
                $mail->setButtonUrl($url);
                $mail->setExpiration($endDateObject);

                $app['notification.deliverer']->deliver($mail);
                unset($remaingEmails[$key]);
            }

            //some mails failed
            if (count($remaingEmails) > 0) {
                foreach ($remaingEmails as $mail) {
                    $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', [
                        'usr_id' => $app['authentication']->getUser()->get_id(),
                        'lst'    => $lst,
                        'ssttid' => $ssttid,
                        'dest'   => $mail,
                        'reason' => \eventsmanager_notify_downloadmailfail::MAIL_FAIL
                    ]);
                }
            }
        } elseif (!$token && count($destMails) > 0) { //couldn't generate token
            foreach ($destMails as $mail) {
                $app['events-manager']->trigger('__EXPORT_MAIL_FAIL__', [
                    'usr_id' => $app['authentication']->getUser()->get_id(),
                    'lst'    => $lst,
                    'ssttid' => $ssttid,
                    'dest'   => $mail,
                    'reason' => 0
                ]);
            }
        }

        return $app->json([
            'success' => true,
            'message' => ''
        ]);
    }
}
