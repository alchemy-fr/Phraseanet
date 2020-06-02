<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerRunningUploader;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningUploaderRepository;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use GuzzleHttp\Client;

class PullAssetsWorker implements WorkerInterface
{
    private $messagePublisher;
    private $conf;

    /** @var  WorkerRunningUploaderRepository $repoWorkerUploader */
    private $repoWorkerUploader;

    public function __construct(MessagePublisher $messagePublisher, PropertyAccess $conf, WorkerRunningUploaderRepository $repoWorkerUploader)
    {
        $this->messagePublisher     = $messagePublisher;
        $this->conf                 = $conf;
        $this->repoWorkerUploader   = $repoWorkerUploader;
    }

    public function process(array $payload)
    {
        $config = $this->conf->get(['workers']);

        if (isset($config['pull_assets'])) {
            $config = $config['pull_assets'];
        } else {
            return;
        }

        $uploaderClient = new Client();

        // if a token exist , use it
        if (isset($config['assetToken'])) {
            $res = $this->getCommits($uploaderClient, $config);
            if ($res == null) {
                return;
            }

            // if Unauthorized get a new token first
            if ($res->getStatusCode() == 401) {
                if (($config = $this->generateToken($uploaderClient, $config)) === null) {
                    return;
                };
                $res = $this->getCommits($uploaderClient, $config);
            }
        } else { // if there is not a token , get one from the uploader service
            if (($config = $this->generateToken($uploaderClient, $config)) === null) {
                return;
            };
            if (($res = $this->getCommits($uploaderClient, $config)) === null) {
                return;
            }
        }

        $body = $res->getBody()->getContents();
        $body = json_decode($body,true);
        $commits = $body['hydra:member'];

        $urlInfo = parse_url($config['endpointCommit']);
        $baseUrl = $urlInfo['scheme'] . '://' . $urlInfo['host'] .':'.$urlInfo['port'];

        foreach ($commits as $commit) {
            //  send only payload in ingest-queue if the commit is ack false and it is not being creating
            if (!$commit['acknowledged'] && !$this->isCommitToBeCreating($commit['id'])) {
                $this->messagePublisher->pushLog("A new commit found in the uploader ! commit_ID : ".$commit['id']);

                // this is an uploader PULL mode
                $payload = [
                    'message_type'  => MessagePublisher::ASSETS_INGEST_TYPE,
                    'payload'       => [
                        'assets'    => array_map(function($asset) {
                            return str_replace('/assets/', '', $asset);
                        }, $commit['assets']),
                        'publisher' => $commit['userId'],
                        'commit_id' => $commit['id'],
                        'token'     => $commit['token'],
                        'base_url'  => $baseUrl,
                        'type'      => WorkerRunningUploader::TYPE_PULL
                    ]
                ];

                $this->messagePublisher->publishMessage($payload, MessagePublisher::ASSETS_INGEST_QUEUE);
            }
        }

    }

    /**
     * @param Client $uploaderClient
     * @param array $config
     * @return \Psr\Http\Message\ResponseInterface|null
     */
    private function getCommits(Client $uploaderClient, array $config)
    {
        try {
            $res = $uploaderClient->get($config['endpointCommit'], [
                'headers' => [
                    'Authorization' => 'AssetToken '.$config['assetToken']
                ]
            ]);
        } catch(\Exception $e) {
            $this->messagePublisher->pushLog("An error occurred when fetching endpointCommit : " . $e->getMessage());

            return null;
        }

        return $res;
    }

    /**
     * @param Client $uploaderClient
     * @param array $config
     * @return array|null
     */
    private function generateToken(Client $uploaderClient, array $config)
    {
        try {
            $tokenBody = $uploaderClient->post($config['endpointToken'], [
                'json' => [
                    'client_id'     => $config['clientId'],
                    'client_secret' => $config['clientSecret'],
                    'grant_type'    => 'client_credentials',
                    'scope'         => 'uploader:commit_list'
                ]
            ])->getBody()->getContents();
        } catch (\Exception $e) {
            $this->messagePublisher->pushLog("An error occurred when fetching endpointToken : " . $e->getMessage());

            return null;
        }

        $tokenBody = json_decode($tokenBody,true);

        $this->conf->set(['workers', 'pull_assets', 'assetToken'], $tokenBody['access_token']);

        return $this->conf->get(['workers', 'pull_assets']);
    }

    /**
     * @param $commitId
     * @return bool
     */
    private function isCommitToBeCreating($commitId)
    {
        $res = $this->repoWorkerUploader->findBy(['commitId' => $commitId]);

        return count($res) != 0;
    }
}
