<?php

namespace Alchemy\Phrasea\WorkerManager\Worker;

use Alchemy\Phrasea\Core\Configuration\PropertyAccess;
use Alchemy\Phrasea\Model\Entities\WorkerRunningJob;
use Alchemy\Phrasea\Model\Repositories\WorkerRunningJobRepository;
use Alchemy\Phrasea\Utilities\NetworkProxiesConfiguration;
use Alchemy\Phrasea\WorkerManager\Queue\MessagePublisher;
use GuzzleHttp\Client;

class PullAssetsWorker implements WorkerInterface
{
    private $messagePublisher;
    private $conf;

    /** @var  WorkerRunningJobRepository $repoWorkerJob */
    private $repoWorkerJob;

    public function __construct(MessagePublisher $messagePublisher, PropertyAccess $conf, WorkerRunningJobRepository $repoWorkerJob)
    {
        $this->messagePublisher     = $messagePublisher;
        $this->conf                 = $conf;
        $this->repoWorkerJob        = $repoWorkerJob;
    }

    public function process(array $payload)
    {
        $configs = $this->conf->get(['phraseanet-service', 'uploader-service']);

        if (isset($configs['pulled_target'])) {
            $configs = $configs['pulled_target'];
        } else {
            $this->messagePublisher->pushLog("No pull target defined in configuration!");

            return;
        }

        foreach ($configs as $targetName => $config) {
            if (empty($config['pullmodeUri'])) {
                $this->messagePublisher->pushLog("No pull mode uri given in this target : " . $targetName);
                continue;
            }

            $verifySsl = isset($config['verify_ssl']) ? $config['verify_ssl'] : true ;

            $proxyConfig = new NetworkProxiesConfiguration($this->conf);
            $clientOptions = [
                'http_errors'   => false,
                'verify'        => $verifySsl
            ];

            // add proxy in each request if defined in configuration
            $uploaderClient = $proxyConfig->getClientWithOptions($clientOptions);

            // if a token exist , use it
            if (isset($config['assetToken'])) {
                $res = $this->getCommits($uploaderClient, $config);
                if ($res == null) {

                    return;
                }

                // if Unauthorized get a new token first
                if ($res->getStatusCode() == 401) {
                    if (($config = $this->generateToken($uploaderClient, $config, $targetName)) === null) {
                        return;
                    };
                    $res = $this->getCommits($uploaderClient, $config);
                }
            } else { // if there is not a token , get one from the uploader service
                if (($config = $this->generateToken($uploaderClient, $config, $targetName)) === null) {
                    return;
                };
                if (($res = $this->getCommits($uploaderClient, $config)) === null) {
                    return;
                }
            }

            $body = $res->getBody()->getContents();
            $body = json_decode($body,true);
            $commits = $body['hydra:member'];

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
                            'base_url'  => $this->getBaseUriFromPullmodeUri($config['pullmodeUri']),
                            'type'      => WorkerRunningJob::TYPE_PULL,
                            'verify_ssl'=> $verifySsl  // to be used for AssetsIngestWorker and the createRecordWorker
                        ]
                    ];

                    $this->messagePublisher->publishMessage($payload, MessagePublisher::ASSETS_INGEST_TYPE);
                }
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
        // get only unacknowledged
        try {
            $res = $uploaderClient->get($config['pullmodeUri'] . '&acknowledged=false', [
                'headers' => [
                    'Authorization' => 'Bearer '. $config['assetToken']
                ]
            ]);
        } catch(\Exception $e) {
            $this->messagePublisher->pushLog("An error occurred when fetching endpoint pullmode uri : " . $e->getMessage());

            return null;
        }

        return $res;
    }

    /**
     * @param Client $uploaderClient
     * @param array $config
     * @return array|null
     */
    private function generateToken(Client $uploaderClient, array $config, $targetName)
    {
        $baseUri = $this->getBaseUriFromPullmodeUri($config['pullmodeUri']);

        try {
            $tokenBody = $uploaderClient->post($baseUri. '/oauth/v2/token', [
                'json' => [
                    'client_id'     => $config['clientId'],
                    'client_secret' => $config['clientSecret'],
                    'grant_type'    => 'client_credentials',
                    'scope'         => 'commit:list'
                ]
            ])->getBody()->getContents();

            $tokenBody = json_decode($tokenBody,true);
        } catch (\Exception $e) {
            $this->messagePublisher->pushLog("An error occurred when fetching endpoint Token : " . $e->getMessage());

            return null;
        }

        $this->conf->set(['phraseanet-service', 'uploader-service', 'pulled_target', $targetName, 'assetToken'], $tokenBody['access_token']);

        return $this->conf->get(['phraseanet-service', 'uploader-service', 'pulled_target', $targetName]);
    }

    /**
     * @param $commitId
     * @return bool
     */
    private function isCommitToBeCreating($commitId)
    {
        $res = $this->repoWorkerJob->findBy(['commitId' => $commitId, 'status'   => WorkerRunningJob::RUNNING]);

        return count($res) != 0;
    }

    private function getBaseUriFromPullmodeUri($pullmodeUri)
    {
        $result = parse_url($pullmodeUri);

        return $result['scheme']."://".$result['host'];
    }
}
