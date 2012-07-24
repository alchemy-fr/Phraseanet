<?php

/*
 * This file is part of Phraseanet
 *
 * (c) 2005-2012 Alchemy
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 *
 * @package     KonsoleKomander
 * @license     http://opensource.org/licenses/gpl-3.0 GPLv3
 * @link        www.phraseanet.com
 */
use Alchemy\Phrasea\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class module_console_systemTemplateGenerator extends Command
{

    public function __construct($name = null)
    {
        parent::__construct($name);

        $this->setDescription('Generate template files');

        return $this;
    }

    public function requireSetup()
    {
        return false;
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $tplDirs = array(
            realpath(__DIR__ . '/../../../../templates/web/'),
            realpath(__DIR__ . '/../../../../templates/mobile/')
        );

        $n_ok = $n_error = 0;

        foreach ($tplDirs as $tplDir) {
            $tmpDir = __DIR__ . '/../../../../tmp/cache_twig/';

            $loader = new Twig_Loader_Filesystem($tplDir);

            $twig = new Twig_Environment($loader, array(
                    'cache'       => $tmpDir,
                    'auto_reload' => true
                ));

            $twig->addExtension(new Twig_Extensions_Extension_I18n());
            $twig->addExtension(new \Alchemy\Phrasea\Twig\JSUniqueID());

            $twig->addFilter('serialize', new \Twig_Filter_Function('serialize'));
            $twig->addFilter('stristr', new \Twig_Filter_Function('stristr'));
            $twig->addFilter('implode', new \Twig_Filter_Function('implode'));
            $twig->addFilter('get_class', new \Twig_Filter_Function('get_class'));
            $twig->addFilter('stripdoublequotes', new \Twig_Filter_Function('stripdoublequotes'));
            $twig->addFilter('geoname_display', new \Twig_Filter_Function('geonames::name_from_id'));
            $twig->addFilter('get_collection_logo', new \Twig_Filter_Function('collection::getLogo'));
            $twig->addFilter('floor', new \Twig_Filter_Function('floor'));
            $twig->addFilter('bas_names', new \Twig_Filter_Function('phrasea::bas_names'));
            $twig->addFilter('sbas_names', new \Twig_Filter_Function('phrasea::sbas_names'));
            $twig->addFilter('urlencode', new \Twig_Filter_Function('urlencode'));
            $twig->addFilter('sbasFromBas', new \Twig_Filter_Function('phrasea::sbasFromBas'));
            $twig->addFilter('key_exists', new \Twig_Filter_Function('array_key_exists'));
            $twig->addFilter('array_keys', new \Twig_Filter_Function('array_keys'));
            $twig->addFilter('round', new \Twig_Filter_Function('round'));
            $twig->addFilter('formatDate', new \Twig_Filter_Function('phraseadate::getDate'));
            $twig->addFilter('prettyDate', new \Twig_Filter_Function('phraseadate::getPrettyString'));
            $twig->addFilter('formatOctets', new \Twig_Filter_Function('p4string::format_octets'));
            $twig->addFilter('geoname_name_from_id', new \Twig_Filter_Function('geonames::name_from_id'));

            $finder = new Symfony\Component\Finder\Finder();
            foreach ($finder->files()->in(array($tplDir))->exclude('Mustache') as $file) {
                try {
                    $twig->loadTemplate(str_replace($tplDir, '', $file->getPathname()));
                    $output->writeln('' . $file . '');
                    $n_ok ++;
                } catch (Exception $e) {
                    $output->writeln('<error>' . $e->getMessage() . '</error>');
                    $n_error ++;
                }
            }
        }

        $output->writeln("");
        $output->write(sprintf('%d templates generated. ', $n_ok));

        if ($n_error > 0) {
            $output->write(sprintf('<error>%d templates failed.</error>', $n_error));
        }

        $output->writeln("");

        return $n_error;
    }
}
