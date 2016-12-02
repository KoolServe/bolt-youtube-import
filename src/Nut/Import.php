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
        $em = $this->app['storage'];
        $repo = $em->getRepository('tracks');

        //Delete the first record. Useful when testing
        // $find = $repo->findOneBy(['id' => 1]);
        // if ($find) {
        //     $result = $repo->delete($find);
        // }

        $key = $this->config['youtubeKey'];
        $playlistId = $this->config['playlistId'];
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=$playlistId&key=$key";

        $uploadFolder = 'tracks/';
        $uploadPath = $this->app['paths']["filespath"] . '/' . $uploadFolder;

        $client = new \GuzzleHttp\Client();
        $request = $client->request('GET', $url);
        $data = json_decode($request->getBody() . '');

        foreach ($data->items as $video) {
            $videoData = $video->snippet;
            $title = $videoData->title;
            $videoId = $videoData->resourceId->videoId;
            $thumbnail = $videoData->thumbnails->standard->url;
            $thumbnailName = strtolower(str_replace(' ', '_', trim($title)));

            //Check that there isn't already a record for this video
            $find = $repo->findOneBy(['youtubeid' => $videoId]);
            if ($find) {
                continue;
            }

            //Save thumbnail
            $client = new \GuzzleHttp\Client();
            $request = $client->request('GET', $thumbnail, ['sink' => $uploadPath . $thumbnailName . '.jpg']);
            $content = $repo->create(['contenttype' => 'tracks', 'status' => 'draft']);
            $data = [
                "title" => $title,
                "youtubeid" => $videoId,
                'image' => $uploadFolder . $thumbnailName . '.jpg'
            ];

            foreach ($data as $key => $value) {
                //dump([$key => $value]);
                $content->set($key, empty($value) ? null : $value);
            }

            $em->save($content);
            $output->writeln('Imported "' . $title . '"');
        }
    }
}