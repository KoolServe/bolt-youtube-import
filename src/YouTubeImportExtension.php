<?php

namespace Bolt\Extension\Koolserve\YouTubeImport;

use Bolt\Application;
use Bolt\Extension\SimpleExtension;

use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * YouTubeImport extension class.
 *
 * @author Chris Hilsdon <chris@koolserve.uk>
 */
class YouTubeImportExtension extends SimpleExtension
{
    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig()
    {
        return [
            'mapping' => [
                'title' => 'title',
                'youtubeid' => 'youtubeid',
                'image' => 'image'
            ],
            'uploadPath' => 'tracks/'
        ];
    }

    protected function registerNutCommands(\Pimple $container)
    {
        $config = $this->getConfig();
        return [
            new Nut\Import($container, $config),
        ];
    }
}
