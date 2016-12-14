<?php

namespace Bolt\Extension\Koolserve\YouTubeImport;

use Bolt\Extension\SimpleExtension;

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
                'image' => 'image',
            ],
            'uploadPath' => 'tracks/',
            'userid' => 1,
            'pages' => 1
        ];
    }

    protected function registerNutCommands(\Pimple $container)
    {
        $config = $this->getConfig();

        return [
            new Nut\Import($container, $config),
            new Nut\Reset($container, $config),
        ];
    }
}
