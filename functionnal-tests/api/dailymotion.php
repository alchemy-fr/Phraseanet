<?php

require_once __DIR__ . '/../../lib/classes/bootstrap.class.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Yaml\Yaml;

\bootstrap::register_autoloads();
$core = \bootstrap::execute('dev');

$console = new Application('Functionnal tests for dailymotion API');

$console
    ->register('upload:dailymotion')
    ->setDescription('Test upload on dailymotion API')
    ->setCode(function (InputInterface $input, OutputInterface $output) use ($core) {
         try {
            $configuration = Yaml::parse(__DIR__. '/config/keys.conf.yml');
        } catch(\Exception $e) {
            $output->writeln('<error>could not parse configuration file</error>');
            return;
        }
        
        
        $appbox = \appbox::get_instance($core);
        
        $found = false;
        foreach ($appbox->get_databoxes() as $databox) {
            /* @var $databox \databox */
            $sql = 'SELECT record_id FROM record WHERE type="video" AND (
                mime="video/mp4" OR mime="video/quicktime" OR mime="video/x-msvideo" OR mime="video/x-msvideo"
            )  LIMIT 1';
            $stmt = $databox->get_connection()->prepare($sql);
            $stmt->execute();
            $rows = $stmt->fetch(\PDO::FETCH_ASSOC);
            if(1 === count($rows)) {
                $found = true;
                $record = $databox->get_record($rows['record_id']);
                break;
            }
            unset($stmt);
        }
        
        if (!$found) {
            $output->writeln('<error>No video found, </error>');
            return;
        }
        
        $bridge = new \Bridge_Api_Dailymotion($core['Registry'], new \Bridge_Api_Auth_OAuth2());
        
        $bridge->set_oauth_token($configuration['dailymotion']['dev_token']);
        
        $options = array();
        $samples = array(
            'title'         => $record->get_title(),
            'description'   => 'Upload functionnal test',
            'tags'          => 'phraseanet upload test dm api',
            'private'       => true,
        );
        
        foreach($bridge->get_fields() as $field) {
           $options[$field['name']] = isset($samples[$field['name']]) ? $samples[$field['name']] : uniqid('test_upload'); 
        }
        
        try {
            $idVideo = $bridge->upload($record, $options);
            $output->writeln(sprintf('<info>video id is %s</info>', $idVideo));
        } catch(\Bridge_Exception_ActionAuthNeedReconnect $e) {
            $output->writeln(sprintf('<error>Invalid token %s</error>', $configuration['dailymotion']['dev_token']));
        } catch(\Exception $e) {
            $output->writeln(sprintf('<error>%s : %s -> %s</error>',get_class($e), $e->getMessage(), $e->getTraceAsString()));
        }
    });

$console->run();
