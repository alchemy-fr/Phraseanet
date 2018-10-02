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
use Alchemy\Phrasea\Application\Helper\DelivererAware;
use Alchemy\Phrasea\Application\Helper\DispatcherAware;
use Alchemy\Phrasea\Application\Helper\FilesystemAware;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\Http\DeliverDataInterface;
use Alchemy\Phrasea\Model\Entities\Token;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class DoDownloadController extends Controller
{
    use DelivererAware;
    use DispatcherAware;
    use FilesystemAware;

    /**
     * Prepare a set of documents for download
     *
     * @param Request     $request
     * @param Token       $token
     *
     * @return Response
     */
    public function prepareDownload(Request $request, Token $token)
    {
        if (false === $list = @unserialize($token->getData())) {
            $this->app->abort(500, 'Invalid datas');
        }

        if (!is_array($list)) {
            $this->app->abort(500, 'Invalid datas');
        }

        foreach (['export_name', 'files'] as $key) {
            if (!isset($list[$key])) {
                $this->app->abort(500, 'Invalid datas');
            }
        }

        $records = [];

        foreach ($list['files'] as $file) {
            if (!is_array($file) || !isset($file['base_id']) || !isset($file['record_id'])) {
                continue;
            }
            $sbasId = \phrasea::sbasFromBas($this->app, $file['base_id']);

            try {
                $record = new \record_adapter($this->app, $sbasId, $file['record_id']);
            } catch (\Exception $e) {
                continue;
            }

            $records[sprintf('%s_%s', $sbasId, $file['record_id'])] = $record;
        }

        return new Response($this->render(
            '/prod/actions/Download/prepare.html.twig', [
            'module_name'   => $this->app->trans('Export'),
            'module'        => $this->app->trans('Export'),
            'list'          => $list,
            'records'       => $records,
            'token'         => $token,
            'anonymous'     => $request->query->get('anonymous', false),
            'type'          => $request->query->get('type', \Session_Logger::EVENT_EXPORTDOWNLOAD)
        ]));
    }

    /**
     * Download a set of documents
     *
     * @param Token       $token
     *
     * @return Response
     */
    public function downloadDocuments(Token $token)
    {
        if (false === $list = @unserialize($token->getData())) {
            $this->app->abort(500, 'Invalid datas');
        }
        if (!is_array($list)) {
            $this->app->abort(500, 'Invalid datas');
        }

        foreach (['export_name', 'files'] as $key) {
            if (!isset($list[$key])) {
                $this->app->abort(500, 'Invalid datas');
            }
        }

        $exportName = $list['export_name'];

        if ($list['count'] === 1) {
            $file = end($list['files']);
            $subdef = end($file['subdefs']);
            $exportName = sprintf('%s%s.%s', $file['export_name'], $subdef['ajout'], $subdef['exportExt']);
            $exportFile = \p4string::addEndSlash($subdef['path']) . $subdef['file'];
            $mime = $subdef['mime'];
            $list['complete'] = true;
        } else {
            $exportFile = $this->app['tmp.download.path'].'/'.$token->getValue() . '.zip';
            $mime = 'application/zip';
        }

        if (!$this->getFilesystem()->exists($exportFile)) {
            $this->app->abort(404, 'Download file not found');
        }

        $this->getDispatcher()->addListener(KernelEvents::RESPONSE, function (FilterResponseEvent $event) use ($list) {
            \set_export::log_download(
                $this->app,
                $list,
                $event->getRequest()->get('type'),
                !!$event->getRequest()->get('anonymous', false),
                (isset($list['email']) ? $list['email'] : '')
            );
        });

        return $this->deliverFile($exportFile, $exportName, DeliverDataInterface::DISPOSITION_ATTACHMENT, $mime);
    }

    /**
     * Build a zip of downloaded documents
     *
     * @param Token       $token
     *
     * @return Response
     */
    public function downloadExecute(Token $token)
    {
        if (false === $list = @unserialize($token->getData())) {
            return $this->app->json([
                'success' => false,
                'message' => 'Invalid datas'
            ]);
        }

        set_time_limit(0);
        // Force the session to be saved and closed.
        /** @var Session $session */
        $session = $this->app['session'];
        $session->save();
        ignore_user_abort(true);

        if ($list['count'] > 1) {
            \set_export::build_zip(
                $this->app,
                $token,
                $list,
                sprintf('%s/%s.zip', $this->app['tmp.download.path'], $token->getValue()) // Dest file
            );
        } else {
            $list['complete'] = true;
            $token->setData(serialize($list));
            /** @var EntityManagerInterface $manager */
            $manager = $this->app['orm.em'];
            $manager->persist($token);
            $manager->flush();
        }

        return $this->app->json([
            'success' => true,
            'message' => ''
        ]);
    }
}
