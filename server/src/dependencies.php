<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// error handler
$container['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        $data = [
            'code' => $exception->getCode(),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => explode("\n", $exception->getTraceAsString()),
        ];

        return $c->get('response')
            ->withStatus(500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));
    };
};

$container["db"] = function ($c) {
    $settings = $c->get('settings')['database'];

    $dsn = $settings['dsn'];
    $username = $settings['username'];
    $password = $settings['password'];

    $pdo = new PDO($dsn, $username, $password);
    $db = new NotORM($pdo);

    $db->debug = function($query, $parameters) use($c) {
        $logger = $c->get('logger');
        $logger->debug('query: '.$query.' '.implode(",", $parameters));
    };

    return $db;
};
