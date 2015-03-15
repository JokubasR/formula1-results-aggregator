<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

//Request::setTrustedProxies(array('127.0.0.1'));

$app['app.manager.data'] = function() {
    return new \Manager\DataManager();
};

$app
    ->get('/', function () use ($app) {
        $grandPrix = $app['app.manager.data']->getGrandPrix();
        $drivers   = $app['app.manager.data']->getDrivers();
        $teams     = $app['app.manager.data']->getTeams();
        $engines   = $app['app.manager.data']->getEngines();

        return $app['twig']->render('index.html.twig', [
            'grandPrix' => $grandPrix,
            'drivers'   => $drivers,
            'teams'     => $teams,
            'engines'   => $engines,
        ]);
    })
    ->bind('homepage')
;

$app
    ->get('/{slug}/qualifying', function($slug) use ($app) {
        $result = $app['app.manager.data']->getGrandPrixQualifyingResult($slug);

        return $app['twig']->render('qualifying.html.twig', [
            'result' => $result,
        ]);
    })
    ->bind('stage_qualifying_results')
;

$app
    ->get('/{slug}/race', function($slug) use ($app) {
        $result = $app['app.manager.data']->getGrandPrixRaceResult($slug);

        return $app['twig']->render('race.html.twig', [
            'result' => $result,
        ]);
    })
    ->bind('stage_race_results')
;

$app->error(function (\Exception $e, Request $request, $code) use ($app) {
    if ($app['debug']) {
        return;
    }

    // 404.html, or 40x.html, or 4xx.html, or error.html
    $templates = array(
        'errors/'.$code.'.html',
        'errors/'.substr($code, 0, 2).'x.html',
        'errors/'.substr($code, 0, 1).'xx.html',
        'errors/default.html',
    );

    return new Response($app['twig']->resolveTemplate($templates)->render(array('code' => $code)), $code);
});
