<?php

use Phalcon\Loader;
use Phalcon\Mvc\Micro;
use Phalcon\Http\Response;
use Phalcon\Di\FactoryDefault;
use Phalcon\Db\Adapter\Pdo\Mysql as PdoMysql;

// User Loader() to autoload our model
$loader = new Loader();

$loader->registerNamespaces([
    "My\\Models" => __DIR__ . "/models/"
]);

$loader->register();

$di = new FactoryDefault();

// Set up the database service
$di->set(
    "db", function() {
        return new PdoMysql([
            "host" => "localhost",
            "username" => "root",
            "password" => "",
            "dbname" => "phalcon_api"
        ]);
    }
);

// Create and bind the DI to the application
$app = new Micro($di);

$app->get('/', function(){
    echo "API";
});

$app->get('/api/test', function(){
    echo "test ok";
});

$app->get('/api/notes', function() use ($app) {
    $phql = "SELECT * FROM My\\Models\\Notes ORDER BY id DESC";
    $notes = $app->modelsManager->executeQuery($phql);
    $data = [];
    foreach($notes as $note) {
        $data[] = [
            "id" => $note->id,
            "name" => $note->name
        ];
    }
    echo json_encode($data);
});

$app->get('/api/notes/search/{name}', function($name) use ($app) {
    $phql = "SELECT * FROM My\\Models\\Notes WHERE name LIKE :name: ORDER BY id DESC";
    $notes = $app->modelsManager->executeQuery($phql, ["name" => "%$name%"]);
    $data = [];
    foreach($notes as $note) {
        $data[] = [
            "id" => $note->id,
            "name" => $note->name
        ];
    }
    echo json_encode($data);
});

$app->get('/api/notes/{id:[0-9]+}', function($id) use ($app) {
    $phql = "SELECT * FROM My\\Models\\Notes WHERE id = :id:";
    $note = $app->modelsManager->executeQuery($phql, ["id" => $id])->getFirst();
    // Create a response
    $response = new Response();
    if ($note === false) {
        $response->setJsonContent([
            "status" => "NOT-FOUND"
        ]);
    } else {
        $response->setJsonContent([
            "status" => "FOUND",
            "data" => [
                "id" => $note->id,
                "name" => $note->name
            ]
        ]);
    }
    return $response;
});

$app->post('/api/notes', function() use ($app) {
    $note = $app->request->getJsonRawBody();
    $phql = "INSERT INTO My\\Models\\Notes (name, note, slug) VALUES (:name:, :note:, :slug:)";
    // $status = $app->modelsManager->executeQuery($phql, [
    //     "name" => $note->name,
    //     "note" => $note->note,
    //     "slug" => $note->slug
    // ]);
    $status = $app->modelsManager->executeQuery($phql, [
        "name" => $app->request->getPost('name'),
        "note" => $app->request->getPost('note'),
        "slug" => $app->request->getPost('slug')
    ]);

    // Create a response
    $response = new Response();

    // Check if the insertion was successful
    if ($status->success() === true) {
        // Change the HTTP status
        $response->setStatusCode(201, "Created");

        $note->id = $status->getModel()->id;

        $response->setJsonContent([
            "status" => "OK",
            "data"   => $note,
            "json"   => $app->request->getJsonRawBody()
        ]);
    } else {
        // Change the HTTP status
        $response->setStatusCode(409, "Conflict");

        // Send errors to the client
        $errors = [];

        foreach ($status->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }

        $response->setJsonContent([
            "status"   => "ERROR",
            "messages" => $errors,
        ]);
    }

    return $response;
});

// Update note by id
$app->put('/api/notes/{id:[0-9]+}', function($id) use ($app) {
    $note = $app->request->getJsonRawBody();

    $phql = "UPDATE My\\Models\\Notes SET name = :name:, note = :note:, slug = :slug: WHERE id = :id:";

    $status = $app->modelsManager->executeQuery($phql, [
        "id"   => $id,
        "name" => $note->name,
        "note" => $note->note,
        "slug" => $note->slug
    ]);

    // Create a response
    $response = new Response();

    // Check if the insertion was successful
    if ($status->success() === true) {
        $response->setJsonContent([
            "status" => "OK"
        ]);
    } else {
        // Change the HTTP status
        $response->setStatusCode(409, "Conflict");

        $errors = [];

        foreach ($status->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }

        $response->setJsonContent([
            "status"   => "ERROR",
            "messages" => $errors
        ]);
    }

    return $response;
});

$app->delete('/api/notes/{id:[0-9]+}', function($id) use ($app) {
    $phql = "DELETE FROM My\\Models\\Notes WHERE id = :id:";
    $status = $app->modelsManager->executeQuery($phql,[ "id" => $id]);

    // Create a response
    $response = new Response();

    if ($status->success() === true) {
        $response->setJsonContent(["status" => "OK"]);
    } else {
        // Change the HTTP status
        $response->setStatusCode(409, "Conflict");

        $errors = [];

        foreach ($status->getMessages() as $message) {
            $errors[] = $message->getMessage();
        }

        $response->setJsonContent([
            "status"   => "ERROR",
            "messages" => $errors,
        ]);
    }

    return $response;
});


$app->handle();