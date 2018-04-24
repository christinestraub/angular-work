<?php

require_once 'controllers/archive_controller.php';
require_once 'controllers/snapshot_controller.php';
require_once 'controllers/things_controller.php';
require_once 'controllers/tag_controller.php';
require_once 'controllers/statistics_controller.php';
require_once 'controllers/auth_controller.php';
require_once 'controllers/util_controller.php';
require_once 'controllers/user_controller.php';

$app->get('/[{name}]', function ($request, $response, $args) {
    // Render index view
    return $this->renderer->render($response, 'index.html', $args);
});

$app->group('/api', function() use ($app) {

    $app->group('/auth', function() use ($app) {
        $app->post('/basic', '\AuthController:basic');
        $app->post('/local', '\AuthController:local');
    });

    $app->group('/users', function() use ($app) {
        $app->get('/me', '\AuthController:me');
    });

    $app->group('/things', function() use ($app) {
        $app->get('', '\ThingsController:index');
        $app->get('/{id}', '\ThingsController:show');
        $app->post('', '\ThingsController:create');
        $app->put('/{id}', '\ThingsController:update');
        $app->patch('/{id}', '\ThingsController:update');
        $app->delete('/{id}', '\ThingsController:destroy');
    });

    $app->group('/archives', function() use ($app) {

        $app->get('', '\ArchiveController:getLatest');
        $app->post('', '\ArchiveController:insert');
        $app->put('', '\ArchiveController:update');
        $app->get('/all', '\ArchiveController:getAll');
        $app->get('/{uuid}', '\ArchiveController:get');
        $app->delete('/{uuid}', '\ArchiveController:delete');
        $app->post('/upload', '\ArchiveController:upload');
        $app->get('/download/{uuid}', '\ArchiveController:download');
        $app->get('/parse/{uuid}', '\ArchiveController:parse');
        $app->post('/previous', '\ArchiveController:previous');
    });


    /* api for old files */
    $app->group('/old', function() use ($app) {
        // get old files
        $app->get('', '\ArchiveController:getOld');
        $app->delete('/{id}/{month}', '\ArchiveController:deleteOld');
    });

    /* api for recycled */
    $app->group('/recycled', function() use ($app) {
        // get recycled items
        $app->get('', '\ArchiveController:getRecycled');
        // delete recycled item
        $app->delete('/{id}', '\ArchiveController:deleteRecycled');
        // send to recycled item
        $app->post('', '\ArchiveController:putRecycled');
        // restore recycled item
        $app->put('', '\ArchiveController:restoreRecycled');
    });

    $app->get('/regions', '\ArchiveController:regions');

    $app->group('/snapshots', function() use ($app) {

        $app->get('', '\SnapshotController:getList');
        $app->get('/all', '\SnapshotController:getAll');
        $app->get('/{id}', '\SnapshotController:get');
        $app->get('/file/{id}', '\SnapshotController:getByFileId');
        $app->get('/parse/{snapshotId}', '\SnapshotController:parse');
        $app->delete('/', '\SnapshotController:delete');
        $app->put('/', '\SnapshotController:update');
        $app->get('/page/{start}/{limit}', '\SnapshotController:getPage');
    });

    $app->group('/tags', function() use ($app) {

        $app->get('', '\TagController:getAll');
        $app->post('', '\TagController:insert');
        $app->put('/{id}', '\TagController:update');
        $app->delete('/{id}', '\TagController:delete');
        $app->get('/restore', '\TagController:restore');
    });

    $app->group('/statistics', function() use ($app) {
        $app->get('/uploads/regions', '\StatisticsController:uploadsForRegions');
        $app->get('/uploads/countries', '\StatisticsController:uploadsForCountries');
        $app->get('/uploads/users', '\StatisticsController:uploadsForUsers');
        $app->get('/ccc/regions', '\StatisticsController:cccForRegions');
        $app->get('/ccc/countries', '\StatisticsController:cccForCountries');
    });

    $app->group('/users', function() use ($app) {
        $app->get('', '\UserController:index');
    });

    $app->group('/info', function() use ($app) {
        $app->get('/php', '\UtilController:phpInfo');
    });
});

