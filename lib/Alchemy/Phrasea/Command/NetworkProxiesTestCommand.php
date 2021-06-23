<?php

namespace Alchemy\Phrasea\Command;


use Alchemy\Phrasea\Utilities\NetworkProxiesConfiguration;
use GuzzleHttp\Client;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NetworkProxiesTestCommand extends Command
{
    public function __construct($name = null)
    {
        parent::__construct($name);

        $this
            ->setDescription('Test defined proxy configuration!')
            ->addOption('url', '', InputOption::VALUE_REQUIRED, 'Url to reach on test')
        ;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $urlTest = $input->getOption('url');

        if ($urlTest == null) {
            $urlTest = 'www.google.fr';
        }

        $proxyConfig = new NetworkProxiesConfiguration($this->container['conf']);
        $clientOptions = [];

        // test http-proxy
        if ($proxyConfig->getHttpProxyConfiguration() != null) {
            $httpProxy = $proxyConfig->getHttpProxyConfiguration();
            $clientOptions = array_merge($clientOptions, ['proxy' => $httpProxy]);

            $client = new Client($clientOptions);

            try {
                $response = $client->get($urlTest);
                $output->writeln("<info>Test outgoing connection with proxy " . $httpProxy ." success with status code : " . $response->getStatusCode() . " </info>");
            } catch(\Exception $e) {
                $output->writeln("<comment>Outgoing connection error with proxy " . $httpProxy . " , " . $e->getMessage() . "</comment>");
            }
        }

        // TODO: add test for ftp and socket proxy

        return 0;
    }
}
