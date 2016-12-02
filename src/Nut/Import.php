<?php

namespace Bolt\Extension\Koolserve\YouTubeImport\Nut;

use Bolt\Extension\Koolserve\YouTubeImport\YouTube;
use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Import extends Command
{
    private $app;

    private $config;

    /**
     * Command constructor
     *
     * @param Application $app
     * @param array $config
     */
    public function __construct(Application $app, array $config)
    {
        parent::__construct();
        $this->app = $app;
        $this->config = $config;
    }

    protected function configure()
    {
        $this
            ->setName('youtube:import')
            ->setDescription('Description of the command')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $import = new YouTube($this->app, $this->config);
        try {
            $import->run();
        } catch (\Exception $e) {
            $trace = $e->getTrace();
            dump($trace[0]['file']);
            dump($trace[0]['line']);
        }
    }
}