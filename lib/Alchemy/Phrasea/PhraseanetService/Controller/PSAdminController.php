<?php

namespace Alchemy\Phrasea\PhraseanetService\Controller;

use Alchemy\Phrasea\Application as PhraseaApplication;
use Alchemy\Phrasea\Controller\Controller;
use Alchemy\Phrasea\PhraseanetService\Form\PSExposeConfigurationType;
use Alchemy\Phrasea\PhraseanetService\Form\PSUploaderConfigurationType;
use Alchemy\Phrasea\WorkerManager\Queue\AMQPConnection;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use Symfony\Component\HttpFoundation\Request;

class PSAdminController extends Controller
{
    public function indexAction(PhraseaApplication $app, Request $request)
    {
        return $this->render('admin/phraseanet-service/index.html.twig', [
            '_fragment'=> $request->get('_fragment') ?? 'expose'
        ]);
    }

    public function authAction()
    {
        return $this->render('admin/phraseanet-service/auth.html.twig');
    }

    public function exposeAction(PhraseaApplication $app, Request $request)
    {
        $exposeConfiguration = $app['conf']->get(['phraseanet-service', 'expose-service'], null);

        $form = $app->form(new PSExposeConfigurationType(), $exposeConfiguration);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $app['conf']->set(['phraseanet-service', 'expose-service'], $form->getData());

            // generate a uniq key between phraseanet service and the phraseanet instance if not exist
            if(!$app['conf']->has(['phraseanet-service', 'phraseanet_local_id'])) {
                $instanceKey = $this->app['conf']->get(['main', 'key']);

                $phraseanetLocalId = md5($instanceKey);

                $app['conf']->set(['phraseanet-service', 'phraseanet_local_id'], $phraseanetLocalId);
            }

            return $app->redirectPath('ps_admin', ['_fragment'=>'expose']);
        }

        return $this->render('admin/phraseanet-service/expose.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function notifyAction()
    {
        return $this->render('admin/phraseanet-service/notify.html.twig');
    }

    public function reportAction()
    {
        return $this->render('admin/phraseanet-service/report.html.twig');
    }

    public function uploaderAction(PhraseaApplication $app, Request $request)
    {
        $uploaderConfiguration = $app['conf']->get(['phraseanet-service', 'uploader-service'], null);
        // the "pullInterval" comes from the ttl_retry
        $ttl_retry = $this->getConf()->get(['workers','queues', MessagePublisher::PULL_ASSETS_TYPE, 'ttl_retry'], null);
        if(!is_null($ttl_retry)) {
            $ttl_retry /= 1000;     // form is in sec
        }
        $uploaderConfiguration['pullInterval'] = $ttl_retry;

        $form = $app->form(new PSUploaderConfigurationType(), $uploaderConfiguration);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            switch($data['act']) {
                case 'save' :   // save the form content (settings) in 2 places
                    $ttl_retry = $data['pullInterval'];
                    unset($data['act'], $data['pullInterval']);
                    $this->getConf()->set(['phraseanet-service', 'uploader-service'], $data);

                    // save ttl in the q settings
                    if(!is_null($ttl_retry)) {
                        $this->getConf()->set(['workers','queues', MessagePublisher::PULL_ASSETS_TYPE, 'ttl_retry'], 1000 * (int)$ttl_retry);
                    }
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::PULL_ASSETS_TYPE]);

                    break;
                case 'start':
                    $this->getAMQPConnection()->setQueue(MessagePublisher::PULL_ASSETS_TYPE);
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::PULL_ASSETS_TYPE]);
                    $this->getMessagePublisher()->initializeLoopQueue(MessagePublisher::PULL_ASSETS_TYPE);

                    break;
                case 'stop':
                    $this->getAMQPConnection()->reinitializeQueue([MessagePublisher::PULL_ASSETS_TYPE]);

                    break;
            }

            return $app->redirectPath('ps_admin', ['_fragment'=>'uploader']);
        }

        // guess if the q is "running" = check if there are pending message on Q or loop-Q
        $running = false;
        $qStatuses = $this->getAMQPConnection()->getQueuesStatus();
        foreach([
                    MessagePublisher::PULL_ASSETS_TYPE,
                    $this->getAMQPConnection()->getLoopQueueName(MessagePublisher::PULL_ASSETS_TYPE)
                ] as $qName) {
            if(isset($qStatuses[$qName]) && $qStatuses[$qName]['messageCount'] > 0) {
                $running = true;
            }
        }

        return $this->render('admin/phraseanet-service/uploader.html.twig', [
            'form' => $form->createView(),
            'running' => $running
        ]);
    }

    /**
     * @return AMQPConnection
     */
    private function getAMQPConnection()
    {
        return $this->app['alchemy_worker.amqp.connection'];
    }

    /**
     * @return MessagePublisher
     */
    private function getMessagePublisher()
    {
        return $this->app['alchemy_worker.message.publisher'];
    }
}
