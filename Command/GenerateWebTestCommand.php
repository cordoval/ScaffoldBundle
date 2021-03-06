<?php

/**
 * (c) 2011 Nicolas Paris <nicolas.caen@gmail.com>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Sweet\ScaffoldBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\Output;
use Symfony\Bundle\FrameworkBundle\Util\Mustache;

class GenerateWebTestCommand extends Command
{
    protected function configure () 
    {
        $this
            ->setName('scaffold:webtest')
            ->setDescription('generate a WebTestCase file')
            ->setDefinition(array(
                new InputArgument('bundle', InputArgument::REQUIRED, 'The bundle'),
                new InputArgument('filename', InputArgument::REQUIRED, 'The name of the file'),
                new InputArgument('path', InputArgument::OPTIONAL, 'The path inside the Tests directory'),
            ))
            ->setHelp(<<<EOT
The <info>scaffold:webtest</info> command create a WebTestCase file.

    <info>scaffold:webtest "vendor\MyBundle" DemoControlleTest Controller</info>
    It will create a webtest file in src/vendor/MyBundle/Tests/Controller/DemoControllerTest.php
EOT
        );
    }

    protected function execute (InputInterface $input, OutputInterface $output) 
    {
        // validate bundle
        if (!preg_match('/Bundle$/', $bundle = $input->getArgument('bundle'))) {
            throw new \InvalidArgumentException('The bundle must end with Bundle');
        }

        // validate that the namespace is at least one level deep
        if (false === strpos($bundle, '\\')) {
            throw new \InvalidArgumentException(
                'The bundle must contain the vendor with quotes, exemple "vendors\MyBundle"');
        }

        if (!is_dir($bundleDir = 'src/'.strtr($bundle, '\\', '/'))) {
            throw new \RuntimeException(sprintf(
                'The directory %s doesn\'t exists, are you sure that it is the correct bundle ?', $bundleDir
            ));
        }

        // validate namespace
        $namespace  = $bundle.'\\Tests';
        $path = $input->getArgument('path');
        if (isset($path)) {
            $namespace .= '\\'.$path;
        }

        $namespace = strtr($namespace, '/', '\\');
        if (preg_match('/[^A-Za-z0-9_\\\-]/', $namespace)) {
            throw new \InvalidArgumentException('The namespace contains invalid characters.');
        }

        if (!preg_match('/Test$/', $filename = $input->getArgument('filename'))) {
            throw new \InvalidArgumentException('The filename musts end with Test');
        }

        $targetFile = 'src/'.strtr($bundle, '\\', '/').'/Tests/'.$path.'/'.$filename.'.php';

        if (file_exists($targetFile)) {
            throw new \RuntimeException(sprintf('The "%s" test file already exists.', $targetFile));
        }

        $filesystem = $this->container->get('filesystem');
        $filesystem->copy(__DIR__.'/../Resources/skeleton/WebTestCase.php', $targetFile);

        Mustache::renderFile($targetFile, array(
            'namespace' => $namespace,
            'className' => $filename,
        ));

        $output->writeln('<info>[File+]</info> '.$targetFile);
    }
}
