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
    protected function registerNutCommands(\Pimple $container)
    {
        return [
            new Import($container, $this->getConfig()),
        ];
    }
}
