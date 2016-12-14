<?php

namespace Bolt\Extension\Koolserve\YouTubeImport\Nut;

use Bolt\Extension\Koolserve\YouTubeImport\YouTube;
use Silex\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Reset extends Command
{
    private $app;

    private $config;

    /**
     * Command constructor.
     *
     * @param Application $app
     * @param array       $config
     */
    public function __construct(Application $app, array $config)
    {
        parent::__construct();
        $this->app = $app;
        $this->config = $config;
    }

    protected function configure()
    {
        $this->setName('youtube:reset')
            ->setDescription('Delete all of the records for the track contenttype')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->app['storage'];
        $repo = $em->getRepository('tracks');

        $find = $repo->findAll();
        if($find) {
            foreach ($find as $f){
                $repo->delete($f);
            }
        }

    }
}
