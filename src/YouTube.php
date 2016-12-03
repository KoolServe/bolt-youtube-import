<?php

/**
 * @author Chris Hilsdon <chris@koolserve.uk>
 */

namespace Bolt\Extension\Koolserve\YouTubeImport;

use Bolt\Application;

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
        $total = $videos->pageInfo->totalResults;

        //If there are more videos to load then get them
        if ($videos->pageInfo->totalResults > count($items)) {
            $total = $videos->pageInfo->totalResults;
            while ($total > count($items)) {
                $videos = $this->fetchVideos($videos->nextPageToken);
                $items = array_merge($items, $videos->items);
                $total = $videos->pageInfo->totalResults;
            }
        }

        $this->processVideos($items);
    }

    protected function fetchVideos($pageToken = '')
    {
        $key = $this->config['youtubeKey'];
        $playlistId = $this->config['playlistId'];
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=$playlistId&key=$key&pageToken=$pageToken";
        dump($url);

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

        foreach ($videos as $video) {
            $videoData = $video->snippet;
            $title = $videoData->title;
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
            $data = [
                $this->getMappedKey('title') => $title,
                $this->getMappedKey('youtubeid') => $videoId,
                $this->getMappedKey('image') => $this->getUploadPath().$thumbnailName.'.jpg',
            ];

            foreach ($data as $key => $value) {
                //dump([$key => $value]);
                $content->set($key, empty($value) ? null : $value);
            }

            //Save that record
            $em->save($content);
        }
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
        return $this->config['mapping'][$key];
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
