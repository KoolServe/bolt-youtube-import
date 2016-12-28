<?php

/**
 * @author Chris Hilsdon <chris@koolserve.uk>
 */

namespace Bolt\Extension\Koolserve\YouTubeImport;

use Bolt\Application;
use Symfony\Component\Debug\Exception\ContextErrorException;

class YouTube
{
    protected $app;

    protected $config;

    public function __construct(Application $app, array $config)
    {
        $this->app = $app;
        $this->config = $config;
    }

    public function run()
    {
        $videos = $this->fetchVideos();
        $items = $videos->items;
        $configMax = ($this->config['pages'] * 50);
        $max = min($videos->pageInfo->totalResults, $configMax);

        //If there are more videos to load then get them
        if ($max > count($items)) {
            $total = $videos->pageInfo->totalResults;
            while ($max > count($items)) {
                $videos = $this->fetchVideos($videos->nextPageToken);
                $items = array_merge($items, $videos->items);
            }
        }

        $this->processVideos($items);
    }

    protected function fetchVideos($pageToken = '')
    {
        $key = $this->config['youtubeKey'];
        $playlistId = $this->config['playlistId'];
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=$playlistId&key=$key&pageToken=$pageToken";

        $client = $this->getClient();
        $request = $client->request('GET', $url);

        return json_decode($request->getBody().'');
    }

    protected function fetchThumbnail($thumbnail, $thumbnailName)
    {
        $uploadPath = $this->app['paths']['filespath'].'/'.$this->getUploadPath();
        $client = $this->getClient();
        $request = $client->request('GET', $thumbnail, [
            'sink' => $uploadPath.$thumbnailName.'.jpg',
        ]);
    }

    protected function processVideos($videos)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository($this->getContenttype());
        $imported = 0;

        foreach ($videos as $video) {
            $videoData = $video->snippet;
            $title = $this->fixTitle($videoData->title);
            $videoId = $videoData->resourceId->videoId;

            //Check that there isn't already a record for this video
            $find = $repo->findOneBy([$this->getMappedKey('youtubeid') => $videoId]);
            if ($find) {
                continue;
            }

            //Get the highest quality thumbnail possible
            $thumbnails = $videoData->thumbnails;
            foreach (['maxres', 'standard', 'high', 'medium', 'default'] as $quality) {
                if (@$thumbnails->$quality) {
                    $thumbnail = $thumbnails->$quality->url;
                    break;
                }
            }

            //Save thumbnail to the uploads directory
            $thumbnailName = strtolower(str_replace(' ', '_', trim($title)));
            $thumbnailName = preg_replace('/[\s\W]+/', '', $thumbnailName);
            $this->fetchThumbnail($thumbnail, $thumbnailName);

            //Create a new record in bolt
            $content = $repo->create([
                'contenttype' => $this->getContenttype(),
                'status' => 'draft',
            ]);
            $content->setOwnerid($this->config['userid']);

            $data = [
                $this->getMappedKey('title') => $title,
                $this->getMappedKey('youtubeid') => $videoId,
                $this->getMappedKey('image') => $this->getUploadPath().$thumbnailName.'.jpg',
            ];

            $data = $this->checkExtraMapping($data);

            foreach ($data as $key => $value) {
                //dump([$key => $value]);
                $content->set($key, empty($value) ? null : $value);
            }

            //Save this new record
            $em->save($content);
            echo "Imported " . $title . "\n";
            $imported++;
        }

        echo "Imported " . $imported . " records \n";

        return $imported;
    }

    private function fixTitle($title)
    {
        $newTitle = str_replace($this->config['exclude'], '', $title);

        //Check to remove double spaces
        if ($this->config['doublespaces']) {
            $newTitle = str_replace('  ', ' ', $newTitle);
        }

        if ($this->config['normalize']) {
            $newTitle = utf8_decode($newTitle);
            $newTitle = ucwords(strtolower($newTitle));
            $newTitle = utf8_encode($newTitle);

        }

        return $newTitle;
    }

    private function checkExtraMapping($data)
    {
        if (!$this->getMappedKey('artist') && !$this->getMappedKey('track')) {
            return $data;
        }

        $title = $data[$this->getMappedKey('title')];

        $explode = explode(' - ', $title);
        if (count($explode) == 2) {
            $data[$this->getMappedKey('artist')] = $explode[0];
            $data[$this->getMappedKey('track')] = $explode[1];
        }

        return $data;
    }

    protected function getEntityManager()
    {
        return $this->app['storage'];
    }

    protected function getContenttype()
    {
        return $this->config['contenttype'];
    }

    protected function getMappedKey($key)
    {
        try {
            $value = $this->config['mapping'][$key];
        } catch (ContextErrorException $e) {
            $value = false;
        }

        return $value;
    }

    protected function getUploadPath()
    {
        return $this->config['uploadPath'];
    }

    protected function getClient()
    {
        return clone $this->app['guzzle.client'];
    }
}
