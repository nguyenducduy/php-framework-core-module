<?php
namespace Core\Service;

use Phalcon\Events\Event as PhEvent;
use Phalcon\Mvc\Dispatcher;
use Shirou\Service\Locator as ShServiceLocator;

class Transformer extends ShServiceLocator
{
    public function beforeExecuteRoute(PhEvent $event, Dispatcher $dispatcher)
    {
        $include = $this->getDI()->get('request')->getQuery('include');

        if (!is_null($include)) {
            $this->getDI()->get('transformer')->parseIncludes($include);
        }
    }
}
