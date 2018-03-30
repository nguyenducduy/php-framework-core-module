<?php
namespace Core;

use Phalcon\DI;
use Phalcon\DiInterface;
use Phalcon\Events\Manager as PhEventsManager;
use Shirou\Bootstrap as ShBootstrap;

class Bootstrap extends ShBootstrap
{
    /**
     * Current module name.
     *
     * @var string
     */
    protected $_moduleName = 'Core';

    /**
     * Bootstrap construction.
     *
     * @param DiInterface $di Dependency injection.
     * @param Manager     $em Events manager object.
     */
    public function __construct(DI $di, PhEventsManager $em)
    {
        parent::__construct($di, $em);

        /**
         * Attach this bootstrap for all application initialization events.
         */
        $em->attach('init', $this);
    }

    /**
     * Init some subsystems after engine initialization.
     */
    public function afterEngine()
    {
        $di = $this->getDI();

        $this->getEventsManager()->attach('dispatch', $di->get('core')->translator());
        $this->getEventsManager()->attach('dispatch', $di->get('core')->transformer());
    }
}
