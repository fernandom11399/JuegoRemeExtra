<?php

namespace App\Http\Controllers\api;

use App\Events\GamesEvent;
use App\Events\StartGameEvent;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\Game as GameEvent;
use App\Events\TurnEvent;

class GameController extends Controller
{
    public function games(){
        if(Auth::user()){
            $games = Game::where('start_at', null)->where('user_1', '!=', Auth::user()->id)->get();
            if(count($games) > 0){
                $games->transform(function ($game) {
                    $user1 = User::find($game->user_1);
                    $user2 = User::find($game->user_2);
                    $game->user_1 = $user1 ? $user1->name : 'Usuario no encontrado';
                    $game->user_2 = $user2 ? $user2->name : 'Usuario no encontrado';
                    return $game ;
                });
                return response()->json([
                    "msg" => "Partias encontradas!!!",
                    'result' => true,
                    'data' => $games
                ], 201);
            } else {
                return response()->json([
                    'result' => false,
                    'msg' => 'No existen juegos.',
                ], 404);
            }
        }else {
            return response()->json([
                'result' => false,
                'msg' => 'Jugador no autenticado.',
            ], 401);
        }
            
        
    }
    public function store()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $juego = new Game();
            $juego->user_1 = $user->id;
            $juego->save();
            //$games = Game::where('start_at', null)->where('user_1', '!=', Auth::user()->id)->get();
            //$games = Game::where('start_at', null)->where('user_1', '!=', Auth::user()->id)->get();
            if($juego->start_at == null){
                event(new GamesEvent($juego));
            }
            // if(count($games) > 0){
            //     event(new GamesEvent($games));
            // }
            return response()->json([
                'mesg' => "Juego creado!!!",
                'result' => true,
                'data' => $juego
            ], 201);
        } else {
            return response()->json([
                'result' => false,
                'msg' => 'Usuario no autenticado.',
            ], 401);
        }
    } 
    public function start(int $id)
    {
        if(Auth::user()){
            $juego = Game::find($id);
            if($juego){
                $juego->user_2 = Auth::user()->id;
                $juego->is_active = true;
                $juego->start_at = now();
                $juego->save();
                event(new StartGameEvent($juego->id));
                return response()->json([
                    "msg" => "Partida iniciada!!!",
                    'result' => true
                ], 201);
            }
            return response()->json([
                "msg" => "Partida no encontrada!!!",
                'result' => false
            ], 404);
        }
        
    } 
    public function cancel(int $id)
    {
        $juego = Game::find($id);
        if($juego){
            $juego->delete();
            $juego->save();
            return response()->json([
                "msg" => "Partida finalizada!!!",
                'result' => true
            ], 201);
        }
        return response()->json([
            "msg" => "Partida no encontrada!!!",
            'result' => false
        ], 404);
    }  
    private function ganadorValidate(Request $request)
    {
        return Validator::make($request->all(), [
            "name" => "required|string|min:3|max:72"
        ]);
    }   
    public function win(Request $request, int $id)
    {
        $juego = Game::find($id);
        if($juego){
            $validate = $this->ganadorValidate($request);
            if ($validate->fails()) {
                return response()->json([
                    'msg' => "Los datos ingresados no cumplen con lo pedido.",
                    'result' => false,
                    'errors' => $validate->errors()  
                ], 422);
            }
            $juego->won = $request->win;
            $juego->save();
            return response()->json([
                "msg" => "Partida finalizada!!!",
                'result' => true
            ], 201);
        }else{
            return response()->json([
                "msg" => "Partida no encontrada.",
                'result' => false
            ], 404);
        }
    }    
    public function cancelAll()
    {
        if(Auth::user()){
            $juegos = Game::where('start_at', null)->where('user_1', Auth::user()->id)->get();
            if(count($juegos) > 0){
                foreach($juegos as $juego){
                    $juego->delete();
                    $juego->save();
                }
                return response()->json([
                    "msg" => "Partidas finalizadas!!!",
                    'result' => true
                ], 201);
            }
            return response()->json([
                "msg" => "No cuentas con partidas pendientes!!!",
                'result' => true
            ], 202);
        }
        return response()->json([
            "msg" => "Usuario no autorizado!!!",
            'result' => false
        ], 401);
    }

    public function turn(int $id)
    {
        if (Auth::check()) {
            $juego = Game::find($id);
            if($juego){
                $user = $juego->user_2;
                if($juego->turn){
                    $user = $juego->user_1;
                }
                $juego->turn = !$juego->turn;
                $juego->save();
                event(new TurnEvent($user));
                return response()->json([
                    "msg" => "Movimiento hecho!!!",
                    'result' => true
                ], 201);
            }
            return response()->json([
                "msg" => "Partida no encontrada!!!",
                'result' => false
            ], 404);
        }
        return response()->json([
            "msg" => "Usuario no auntenticado.",
            'result' => false
        ], 401);
    }
}