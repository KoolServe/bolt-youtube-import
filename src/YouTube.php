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
        $this->processVideos($videos);
    }

    protected function fetchVideos()
    {
        $key = $this->config['youtubeKey'];
        $playlistId = $this->config['playlistId'];
        $url = "https://www.googleapis.com/youtube/v3/playlistItems?part=snippet&maxResults=50&playlistId=$playlistId&key=$key";

        $client = $this->getClient();
        $request = $client->request('GET', $url);
        return json_decode($request->getBody() . '');
    }

    protected function fetchThumbnail($thumbnail, $thumbnailName)
    {
        $uploadPath = $this->app['paths']["filespath"] . '/' . $this->getUploadPath();
        $client = $this->getClient();
        $request = $client->request('GET', $thumbnail, [
            'sink' => $uploadPath . $thumbnailName . '.jpg'
        ]);
    }

    protected function processVideos($videos)
    {
        $em = $this->getEntityManager();
        $repo = $em->getRepository($this->getContenttype());

        foreach ($videos->items as $video) {
            $videoData = $video->snippet;
            $title = $videoData->title;
            $videoId = $videoData->resourceId->videoId;

            //Check that there isn't already a record for this video
            $find = $repo->findOneBy(['youtubeid' => $videoId]);
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

            //Save thumbnail
            $thumbnailName = strtolower(str_replace(' ', '_', trim($title)));
            $thumbnailName = preg_replace('/[\s\W]+/', '', $thumbnailName);
            $this->fetchThumbnail($thumbnail, $thumbnailName);

            $content = $repo->create([
                'contenttype' => $this->getContenttype(),
                'status' => 'draft'
            ]);
            $data = [
                $this->getMappedKey("title") => $title,
                $this->getMappedKey("youtubeid") => $videoId,
                $this->getMappedKey('image') => $this->getUploadPath() . $thumbnailName . '.jpg'
            ];

            foreach ($data as $key => $value) {
                //dump([$key => $value]);
                $content->set($key, empty($value) ? null : $value);
            }
            
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