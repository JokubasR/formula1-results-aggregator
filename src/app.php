<?php

use Silex\Application;
use Silex\Provider\TwigServiceProvider;
use Silex\Provider\RoutingServiceProvider;
use Silex\Provider\ValidatorServiceProvider;
use Silex\Provider\ServiceControllerServiceProvider;
use Silex\Provider\HttpFragmentServiceProvider;
use Beryllium\SilexCacheProvider\SilexCacheProvider;

$app = new Application();
$app->register(new RoutingServiceProvider());
$app->register(new ValidatorServiceProvider());
$app->register(new ServiceControllerServiceProvider());
$app->register(new TwigServiceProvider());
$app->register(new HttpFragmentServiceProvider());

$app['app.manager.data']   = function () use ($app) {
    return new \Manager\DataManager($app['be_cache']);
};
$app['app.manager.points'] = function () use ($app) {
    return new \Manager\PointsManager($app['be_cache']);
};

$app['twig'] = $app->extend('twig', function (Twig_Environment $twig, $app) {
    // add custom globals, filters, tags, ...

    $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset) use ($app) {
        return $app['request_stack']->getMasterRequest()->getBasepath().'/'.$asset;
    }));

    $twig->addGlobal('current_race', $app['app.manager.data']->getCurrentRace());

    return $twig;
});

$app->register(new SilexCacheProvider(), [
        'be_cache.type' => 'filecache',
        'be_cache.path' => __DIR__.'/../var/cache/',
    ]
);

return $app;
