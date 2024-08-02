<?php

use App\Http\Controllers\api\CustomConnectorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



// Route::get('/customConnectors', [CustomConnectorController::class, 'index']);
// Route::post('/createConnector', [CustomConnectorController::class, 'createConnector']);
// Route::post('/addStream/{id}', [CustomConnectorController::class, 'addStream']);
// Route::get('/testStream/{id}/{streamIndex}', [CustomConnectorController::class, 'testStream']);

Route::get('customConnectors', [CustomConnectorController::class, 'index']);
Route::post('createConnector', [CustomConnectorController::class, 'createConnector']);
Route::post('addStream/{id}', [CustomConnectorController::class, 'addStream']);
Route::get('testStreamByUrl/{url}', [CustomConnectorController::class, 'testStreamByUrl']);
Route::get('testUrl/', [CustomConnectorController::class, 'testUrl']);

Route::post('publish/',[CustomConnectorController::class, 'publishConnector']);