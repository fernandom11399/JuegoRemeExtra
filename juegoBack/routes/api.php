<?php

use App\Http\Controllers\api\ActivationController;
use App\Http\Controllers\api\GameController;
use App\Http\Controllers\api\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
 
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::prefix('user')->group(function (){
    Route::get('/activate-account', [ActivationController::class, 'activate'])->name('activate.account');
    Route::post('/code', [UserController::class, 'codeGenerate']); //Recibe correo y contraseña
    Route::post('/login', [UserController::class, 'login']); //Recibe correo y código
    Route::post('/register', [UserController::class, 'store']); // Recibe name, email y password
});      

Route::get('/prueba', function () {
    return 1;
});

//Recibe token en header, se mandará como Authorization y en value ira Bearer {token}
Route::middleware(['auth:sanctum', 'verificarCuentaActiva'])->group(function () {
    //RUD de usuarios
    Route::prefix('user')->group(function (){
        Route::get('/search', [UserController::class, 'index']);
        Route::put('/update', [UserController::class, 'update']); // Puede recibir name, email y password 

        Route::post('/logout', [UserController::class, 'logout']); 
        Route::get('/user-info', [UserController::class, 'userInfo']);
        Route::delete('/delete', [UserController::class, 'destroy']);
        Route::get('/historial', [UserController::class, 'historial']);
    });
    Route::prefix('game')->group(function (){
        Route::post('/create', [GameController::class, 'store']);
        Route::get('/games', [GameController::class, 'games']);
        Route::put('/start/{id}', [GameController::class, 'start'])->where('id', '[0-9]+');
        Route::delete('/cancel/{id}', [GameController::class, 'cancel'])->where('id', '[0-9]+');
        Route::delete('/cancel', [GameController::class, 'cancelAll']);
        Route::post('/win', [GameController::class, 'win'])->where('id', '[0-9]+');
        Route::post('/turn/{id}', [GameController::class, 'turn'])->where('id', '[0-9]+');
    });
});


