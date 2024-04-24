<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParkingVehicle;
use App\Models\Vehicle;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use App\Mail\ActivationMail;
use App\Models\Game;
use App\Models\UsersInOutLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;



class UserController extends Controller
{
    private function EnviarCorreoActivacion(User $user)
    {
        $signedUrl = URL::temporarySignedRoute(
            'activate.account',
            now()->addHours(1),
            ['email' => $user->email]
        );
        
        Mail::send('emails.activation', ['url' => $signedUrl], function ($message) use ($user) {
            $message->to($user->email)
                    ->subject('Activación de cuenta');
        });
    }
    private function registerValidate(Request $request)
    {
        return Validator::make($request->all(), [
            "name" => "required|string|min:3|max:72",
            "password" => "required|string|min:8",
            "email" => "required|email|unique:users|max:320",
        ]);
    }
    public function store(Request $request)
    {
        $user = new User();
        $validate = $this->registerValidate($request);
        if ($validate->fails()) {
            return response()->json([
                'msg' => "Los datos ingresados no cumplen con lo pedido.",
                'result' => false,
                'errors' => $validate->errors()
                
            ], 422);
        }
        $user->name = $request->name;
        $user->email = $request->email;
        $user->password = bcrypt($request->password);
        $this->EnviarCorreoActivacion($user);
        $user->save();
        return response()->json([
            "msg" => "Bienvenido!!!",
            'result' => true,
            "data" => $user
        ], 201);
    }
    private function generateCodeValidate(Request $request)
    {
        return Validator::make($request->all(), [
            "email" => "required|email",
            "password" => "required|string"
        ]);
    }
    private function EnviarCorreoCode(User $user)
    {  
        Mail::raw('Código de verificación: ' . $user->code, function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Código de verificación');
        });
        
        
    }
    public function codeGenerate(Request $request)
    {
        $login = $this->generateCodeValidate($request);

        if($login->fails()){
            return response()->json([
                'result' => false,
                'msg' => 'Datos invalidos.',
                'errors' => $login->errors()
            ], 404);
        }
        $user = User::where('email', $request->email)->first();
        if($user){
            if($user->email_verified_at == null){
                return response()->json([                
                    'result' => false,
                    'msg' => "La cuenta no esta activa."
                ], 401);
            }
            if(Hash::check($request->password, $user->password)){
                    $user->code = Str::random(6);
                    $user->save();
                    $this->EnviarCorreoCode($user);
                    return response()->json([            
                    'result' => true,
                    'msg' => 'Datos correctos, se ha enviado un correo con su código.'
                ]);
            }
            return response()->json([
                'result' => false,
                'msg' => 'Contraseña incorrecta.'
            ], 404);
        }
        return response()->json([
            'result' => false,
            'msg' => 'Correo incorrecto.'
        ], 404);

        
    }
    private function loginValidate(Request $request)
    {
        return Validator::make($request->all(), [
            "email" => "required|email",
            "code" => "required|string"
        ]);
    }
    public function login(Request $request)
    {
        $login = $this->loginValidate($request);

        if($login->fails()){
            return response()->json([
                'result' => false,
                'msg' => 'Datos invalidos.',
                'errors' => $login->errors()
            ], 404);
        }
        $user = User::where('email', $request->email)->where("deleted_at", null)->first();
        if($user){
            if($user->email_verified_at == null){
                return response()->json([                
                    'result' => false,
                    'msg' => "La cuenta no esta activa."
                ], 401);
            }            
            if($user->code = $request->code){
                $token = $user->createToken('sactum-token')->plainTextToken;
                return response()->json([            
                    'result' => true,
                    'msg' => 'Login exitoso.',
                    'data' => [   
                        'user' => $user,
                        'token' => $token
                    ]
                ], 200);
    
            }else{
                return response()->json([
                    'result' => false,
                    'msg' => 'Código incorrecto.',
                ], 404);
            }
    
        }else{
            return response()->json([
                'result' => false,
                'msg' => 'Correo incorrecto.',
            ], 404);
        }
        
    }
    private function updateValidate(Request $request)
    {
        return Validator::make($request->all(), [
            "name" => "required|string|min:3|max:72",
            "password" => "required|string|min:8"
        ]);
    }
    public function update(Request $request)
    {        
        if (Auth::check()) {
            $user = Auth::user();
            $user = User::find($user->id);
            if (!$user) {
                return response()->json([
                    'result' => false,
                    'msg' => "Usuario no encontrado.",
                ], 404);
            }

            $validate = $this->updateValidate($request);

            if ($validate->fails()) {
                return response()->json([
                    'result' => false,                
                    'msg' => "Los datos ingresados no cumplen",
                    'errors' => $validate->errors()
                ], 422);
            }

            $user->name = $request->name;
            $user->password = bcrypt($request->password);
            $user->save();
            return response()->json([
                'msg' => "Datos del usuario actualizados",
                'result' => true,
                'data' => $user
            ], 200);
        } else {
            return response()->json([
                'result' => false,
                'msg' => 'Usuario no autenticado.',
            ], 401);
        }
    }
    public function userInfo()
    {
        if (Auth::check()) {
            $user = Auth::user();
            return response()->json([
                'result' => true,
                'msg' => 'Informacion del usuario.',
                'data' => $user,
            ], 200);
        } else {
            return response()->json([
                'result' => false,
                'msg' => 'Usuario no autenticado.',
            ], 401);
        }
    }
    public function logout(Request $request)
    {
        try {
            $token = $request->user()->currentAccessToken();
            if ($token) {
                $token->delete();
                return response()->json([
                    'result' => true,
                    'msg' => 'Cuenta cerrada.'
                ], 200);
            } else {
                return response()->json([
                    'result' => false,
                    'msg' => 'Usuario no autenticado.'
                ], 401);
            }
        } catch (\Exception $e) {
            return response()->json([
                'result' => false,
                'msg' => 'Error al intentar cerrar la cuenta.',
                'error' => $e
            ], 404);
        }
    }
    public function historial(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if($user){
                $juegos = Game::where('user_1', $user->id)->orWhere('user_2', $user->id)->get();
                if($juegos){
                    Log::info($juegos);
                    $juegos->transform(function ($juego) {
                        $user1 = User::find($juego->user_1);
                        $user2 = User::find($juego->user_2);
                        $juego->user_1 = $user1 ? $user1->name : 'Usuario no encontrado';
                        $juego->user_2 = $user2 ? $user2->name : 'Usuario no encontrado';
                        return $juego;
                    });
                    return response()->json([
                        'result' => true,
                        'msg' => 'Historial de juegos.',
                        'data' => $juegos
                    ], 200);
                }
                return response()->json([
                    'result' => false,
                    'msg' => 'Sin historial.'
                ], 404);
            }
            return response()->json([
                'result' => false,
                'msg' => 'Usuario no existente.',
            ], 404);
        } else {
            return response()->json([
                'result' => false,
                'msg' => 'Usuario no autenticado.',
            ], 401);
        }    
    }
    public function destroy(Request $request)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $user = User::find($user->user_id);
            if (!$user) {
                return response()->json([
                    'result' => false,
                    'msg' => "Cuenta no encontrada.",
                ], 404);
            }
            $this->logout($request);
            $user->delete();
            return response()->json([
                'message' => 'Bye :(.',
                'result' => true
            ], 200);
        }else {
            return response()->json([
                'result' => false,
                'msg' => 'Usuario no autenticado.',
            ], 401);
        }
    }
}