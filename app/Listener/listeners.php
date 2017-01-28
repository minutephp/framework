<?php

/** @var Binding $binding */
use Minute\Controller\Runnable;
use Minute\Event\AuthEvent;
use Minute\Event\Binding;
use Minute\Event\ControllerEvent;
use Minute\Event\ModelEvent;
use Minute\Event\RedirectEvent;
use Minute\Event\RequestEvent;
use Minute\Event\ResponseEvent;
use Minute\Event\SeoEvent;
use Minute\Event\SessionEvent;
use Minute\Render\ModelPrinter;
use Minute\Render\Output;
use Minute\Render\Problem;
use Minute\Render\SessionPrinter;
use Minute\Routing\Router;
use Minute\Seo\SeoData;
use Minute\Session\Session;
use Minute\Tracker\Tracker;

$binding->addMultiple([
    //framework
    ['event' => RequestEvent::REQUEST_HANDLE, 'handler' => [Router::class, 'handle']],
    ['event' => RequestEvent::REQUEST_HANDLE, 'handler' => [Tracker::class, 'track']],

    ['event' => ResponseEvent::RESPONSE_RENDER, 'handler' => [Output::class, 'send'], 'priority' => -100],

    ['event' => ResponseEvent::RESPONSE_ERROR, 'handler' => [Problem::class, 'send']],
    ['event' => AuthEvent::AUTH_CHECK_ACCESS, 'handler' => [Session::class, 'checkAccess']],
    ['event' => ControllerEvent::CONTROLLER_EXECUTE, 'handler' => [Runnable::class, 'execute']],
    ['event' => ModelEvent::IMPORT_MODELS_AS_JS, 'handler' => [ModelPrinter::class, 'importModels']],
    ['event' => SessionEvent::IMPORT_SESSION_AS_JS, 'handler' => [SessionPrinter::class, 'importSession']],

    //Seo
    ['event' => SeoEvent::SEO_GET_TITLE, 'handler' => [SeoData::class, 'getData']],
]);