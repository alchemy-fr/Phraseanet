<?php

namespace Alchemy\Phrasea\Command;


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
        $proxiesConfig = $this->container['conf']->get(['network-proxies']);
        $urlTest = $input->getOption('url');

        if ($urlTest == null) {
            $urlTest = 'www.google.fr';
        }

        if (isset($proxiesConfig['http-proxy']) && $proxiesConfig['http-proxy']['enabled'] && $proxiesConfig['http-proxy']['host'] && $proxiesConfig['http-proxy']['port']) {
            $output->writeln("Begin to check http proxy, maybe it's take a few seconds ....");
            $httpProxy = $proxiesConfig['http-proxy'];
            $client = new Client([
                'http_errors' => true,
                'proxy' => $httpProxy['host'] . ':' . $httpProxy['port']
            ]);

            try {
                $response = $client->get($urlTest);

                $output->writeln("<info>Test outgoing connection with proxy " .$httpProxy['host'] . ':' . $httpProxy['port']." success with status code : " . $response->getStatusCode() . " </info>");
            } catch(\Exception $e) {
                $output->writeln("<comment>Outgoing connection error with proxy " . $httpProxy['host'] . ':' . $httpProxy['port'] . " , " . $e->getMessage() . "</comment>");
            }
        }

        return 0;
    }
}
