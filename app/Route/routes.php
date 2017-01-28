<?php

/** @var Router $router */
use Minute\Model\Permission;
use Minute\Routing\Router;

$router->get('/static/_resource/{name}', 'Generic/ResourceLoader', false)->setDefault('_noView', true);