<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2016 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Alchemy\Phrasea\Command\Developer;

use Alchemy\Phrasea\Command\Command;
use Alchemy\Phrasea\Exception\RuntimeException;
use Alchemy\Phrasea\Utilities\StringHelper;
use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Process\Process;

class SetupTestsDbs extends Command
{
    public function __construct()
    {
        parent::__construct('ini:setup-tests-dbs');

        $this->setDescription('Setup dbs for tests environment');
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        if (!$this->container['phraseanet.configuration']->isSetup()) {
            throw new RuntimeException(sprintf(
                'Phraseanet is not setup. You can run <info>bin/setup system::install</info> command to install Phraseanet.'
            ));
        }

        $settings = Yaml::parse(file_get_contents(__DIR__ . '/../../../../../resources/hudson/InstallDBs.yml'));

        $dbs = array();

        $dbs[] = $settings['database']['ab_name'];
        $dbs[] = $settings['database']['db_name'];

        /** @var Connection $connection */
        $connection = $this->container['orm.em']->getConnection();
        $schema = $connection->getSchemaManager();

        foreach($dbs as $name) {
            $output->writeln('Creating database "'.$name.'"...<info>OK</info>');
            $name = StringHelper::SqlQuote($name, StringHelper::SQL_IDENTIFIER);    // quote as `identifier`
            $schema->dropAndCreateDatabase($name);
        }

        $user = StringHelper::SqlQuote($settings['database']['user'], StringHelper::SQL_VALUE); // quote as 'value'
        $host = StringHelper::SqlQuote($settings['database']['host'], StringHelper::SQL_VALUE);
        $pass = StringHelper::SqlQuote($settings['database']['password'], StringHelper::SQL_VALUE);

        $ab_name = StringHelper::SqlQuote($settings['database']['ab_name'], StringHelper::SQL_IDENTIFIER);
        $db_name = StringHelper::SqlQuote($settings['database']['db_name'], StringHelper::SQL_IDENTIFIER);
/*
        $this->container['orm.em']->getConnection()->executeUpdate(
            'CREATE USER '.$user.'@'.$host.' IDENTIFIED WITH mysql_native_password BY '.$pass
        );

        $this->container['orm.em']->getConnection()->executeUpdate(
            'GRANT ALL PRIVILEGES ON '.$ab_name.'.* TO '.$user.'@'.$host
        );

        $this->container['orm.em']->getConnection()->executeUpdate(
            'GRANT ALL PRIVILEGES ON '.$db_name.'.* TO '.$user.'@'.$host
        );
*/
        $this->container['orm.em']->getConnection()->executeUpdate('SET @@global.sql_mode= ""');

        return 0;
    }
}
