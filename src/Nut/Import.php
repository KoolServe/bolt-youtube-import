<?php

namespace Bolt\Extension\Koolserve\YouTubeImport\Nut;

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
        $repo = $this->app['storage'];
        $pages = $repo->getRepository('tracks');

        $key = $this->config['youtubeKey'];
        $playlistId = $this->config['playlistId'];
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=$playlistId&key=$key";

        $client = new \GuzzleHttp\Client();
        $request = $client->request('GET', $url);
        $data = json_decode($request->getBody() . '');

        foreach ($data->items as $video) {
            $videoData = $video->snippet;
            $title = $videoData->title;
            $videoId = $videoData->resourceId->videoId;
            //$thumbnail = $videoData->thumbnails->standard->url;

            //Save thumbnail - todo later
            //$client = new \GuzzleHttp\Client();
            //$request = $client->request('GET', $thumbnail, ['sink' => sys_get_temp_dir() . '_bolt_' . $videoId . '.jpg']);

            $content = $pages->create(['contenttype' => 'tracks', 'status' => 'draft']);
            $data = [
                "title" => $title,
                "youtubelink" => 'https://youtu.be/' . $videoId
            ];

            foreach ($data as $key => $value) {
                $content->set($key, empty($value) ? null : $value);
            }

            $repo->save($content);
            $output->writeln('Imported "' . $title . '""');
        }
    }
}