<?php
namespace App\Http\Controllers\Auth;

use App\User;
use App\Invitation;
use App\Game;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Image;
use Storage;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecoveryPassword;
use App\Message;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
            //'remember_me' => 'boolean'
        ]);
        $credentials = request(['email', 'password']);
        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Correo o Contraseña Incorrectos!!'
            ], 401);
        }
        $user = $request->user();
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->token;
        if ($request->remember_me) {
            $token->expires_at = Carbon::now()->addWeeks(1);
        }
        $token->save();
        return response()->json([
            'access_token' => $tokenResult->accessToken,
            'token_type' => 'Bearer',
            'expires_at' => Carbon::parse(
                $tokenResult->token->expires_at
            )->toDateTimeString()
        ]);
    }
    public function register(Request $request)
    {
        $request->validate([
            'fName' => 'required|string',
            'lName' => 'required|string',
            'email' => 'required|string|email|unique:users',
            'dni' => 'required|string',
            'phone' => 'required|string|min:6',
            'nickname' => 'required|string',
            'code' => 'required|string',
            //'photo' => 'required',
            'password' => 'required|string'
        ]);

        //Valid Code
        $pre_invitation = null;
        $pre_user_id = null;

        // If code exist 
        $code = $request->code;
        if($code != ''){
            $user = User::where('code', '=', $code)->first();
            // Check Code
            if(!$user){
                return response()->json('El codigo es Invalido!!', 400);
            }
            $pre_user_id = $user->id;

            // Check is game of CODE is allow
            if($pre_user_id){
                $pre_invitation = Invitation::select('invitations.*','games.approved')
                ->where('invitations.invited_user_id','=',$pre_user_id)
                ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
                ->where('invitations.status','=',1)
                ->where('games.status','=',1)
                ->orderBy('created_at', 'desc')
                ->first();
            }

            if($pre_invitation){
                // Check is user of CODE payment
                if(!$pre_invitation->approved){
                    return response()->json('Este codigo esta pendiente por Pago!!', 400);
                }
            }else{
                //First User No invitation
                if($pre_user_id != 2){
                    return response()->json('Este codigo no esta totalmente Aprobado!!', 400);
                }
            }

            $level_1_game = Invitation::select('invitations.*')
            ->where('invitations.level_1','=',$pre_user_id)
            ->where('invitations.status','=',1)
            ->count();
            
            // Check is user of CODE is available by limit
            if($level_1_game >= config('api.max_players_level')){
                return response()->json('Este codigo ha superado el limite de Invitados!!.', 400);
            }
            
        }

        //Create User
        $user = new User;
        $user->name = $request->fName;
        $user->last_name = $request->lName;
        $user->email = $request->email;
        $user->dni = $request->dni;
        $user->phone = $request->phone;
        $user->nickname = $request->nickname;
        if (!empty($request->input('photo'))) {
            /*$image      = $request->file('photo');
            $img = Image::make($image->getRealPath());
            // FOR RESIZE
            $img->resize(500, 500, function ($constraint) {
                $constraint->aspectRatio();                 
            })->encode('png');
            $img->stream();
            
            //$fileName   = time() . '_' . $dni  . '.' .$image->getClientOriginalExtension();
            $fileName = time() . '_' . $request->dni  . '.' .'png';
            $filePath = 'avatar/'.$request->dni.'/'.'/'.$fileName;
            Storage::disk('public')->put($filePath, $img, 'public');
            $user->photo = $filePath;*/
            $user->photo = $request->input('photo');
        }else{
            if($request->input('avatar') > 0){
                $user->photo = $request->input('avatar').".jpg";
            }else{
               $user->photo = '0.svg';  
            }
           
        }
        
        $user->code = $this->generateRandomString(6);
        // To DO: Validate error code
        $user->password = bcrypt($request->password);
        $user->save();


        //Create Game
        $game = new Game();
        $game->user_id = $user->id;
        $game->save();
 
        //Check Heritage -> Another player lost the game and have children
        $heritage = Invitation::select('invitations.*')
        ->where('invitations.user_id','=',$pre_user_id)
        ->where('invitations.status','=',0)
        ->where('invitations.migrated','=',0)
        ->orderBy('created_at')
        ->first();
    
        if($heritage){
            $user_to_remove = $heritage->invited_user_id;
            $heritage->update(['migrated' => 1]);

            Invitation::where('level_1','=',$user_to_remove)
            ->where('invitations.status','<',3)
            ->where('invitations.migrated','=',0)
            ->update(['level_1' => $user->id,'user_id' => $user->id]);
            Invitation::where('level_2','=',$user_to_remove)
            ->where('invitations.status','<',3)
            ->where('invitations.migrated','=',0)
            ->update(['level_2' => $user->id]);
            Invitation::where('level_3','=',$user_to_remove)
            ->where('invitations.status','<',3)
            ->where('invitations.migrated','=',0)
            ->update(['level_3' => $user->id]);
            Invitation::where('level_4','=',$user_to_remove)
            ->where('invitations.status','<',3)
            ->where('invitations.migrated','=',0)
            ->update(['level_4' => $user->id]);
            Invitation::where('level_5','=',$user_to_remove)
            ->where('invitations.status','<',3)
            ->where('invitations.migrated','=',0)
            ->update(['level_5' => $user->id]);           
        }
        

        //Create Invitation
        $invitation = new Invitation();
        $invitation->user_id = $pre_user_id;
        $invitation->invited_user_id = $user->id;
        $invitation->invited_user_game = $game->id;
        $invitation->level_1 = $pre_user_id ? $pre_user_id : 0;
        $invitation->level_2 = $pre_invitation ? $pre_invitation->level_1 : 0;
        $invitation->level_3 = $pre_invitation ? $pre_invitation->level_2 : 0;
        $invitation->level_4 = $pre_invitation ? $pre_invitation->level_3 : 0;
        $invitation->level_5 = $pre_invitation ? $pre_invitation->level_4 : 0;
        $invitation->status  = 1;
        $invitation->save();

        return response()->json([
            'message' => 'Successfully created user!'
        ], 201);
    }

    public function generateRandomString($length = 20) {
            $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }

    public function logout(Request $request)
    {
        $request->user()->token()->revoke();
        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function user(Request $request)
    {
        return response()->json($request->user());
    }

    /**
     * Get the authenticated User
     *
     * @return [json] user object
     */
    public function userList(Request $request)
    {
        $users = User::select('id','name','last_name','nickname','photo')->get();
        return response()->json($users);
    }

    public function validCode(Request $request)
    {
        $pre_invitation = null;
        $user_id = null;
        $current_user = Auth::id() ? Auth::id() : 0;

        // If code exist 
        $code = $request->code;
        if($code != ''){
            $user = User::where('code', '=', $request->code)->first();
            // Check Code
            if(!$user){
                return response()->json('Wrong Code.', 400);
            }
            $user_id = $user->id;

            // Check self invitation
            if($user_id == $current_user){
                return response()->json('Self invitation not allowed.', 400);
            }

            // Check is game of CODE is allow
            if($user->id){
                $pre_invitation = Invitation::select('invitations.*','games.approved')
                ->where('invitations.invited_user_id','=',$user_id)
                ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
                ->where('invitations.status','=',1)
                ->where('games.status','=',1)
                ->orderBy('created_at', 'desc')
                ->first();
            }

            if($pre_invitation){
                // Check is user of CODE payment
                if(!$pre_invitation->approved){
                    return response()->json('Este codigo esta pendiente de Pago!!', 400);
                }else{
                    $level_1_game = Invitation::select('invitations.*')
                    ->where('invitations.invited_user_id','=',$user_id)
                    ->where('invitations.status','=',1)
                    ->count();
                    // Check is user of CODE is available by limit
                    if($level_1_game >= config('api.max_players_level')){
                        return response()->json('Ya este codigo llego al limite de Invitaciones!!', 400);
                    }
                }
                
            }else{
                return response()->json('The code is not in a game.', 400);
            }
            
        }

        // Check if current playing and 1st objetive
        $current_game = Invitation::where('invited_user_id','=',$current_user)
        ->where('status','>',0)
        ->where('status','<',3)
        ->get();
        if(count($current_game) > 0){
            return response()->json('Multiplies games not allowed.', 400);
        }


        //Create Game
        $game = new Game();
        $game->user_id = $current_user;
        $game->save();

        //Create Invitation
        $invitation = new Invitation();
        $invitation->user_id = $user_id;
        $invitation->invited_user_id = $current_user;
        $invitation->invited_user_game = $game->id;
        $invitation->level_1 = $user_id ? $user_id : 0;
        $invitation->level_2 = $pre_invitation ? $pre_invitation->level_1 : 0;
        $invitation->level_3 = $pre_invitation ? $pre_invitation->level_2 : 0;
        $invitation->level_4 = $pre_invitation ? $pre_invitation->level_3 : 0;
        $invitation->level_5 = $pre_invitation ? $pre_invitation->level_4 : 0;
        $invitation->status  = 1;
        $invitation->save();
    }

    public function users_invited(Request $request){
        $users = DB::table('invitations as i')->select('u.id','u.name','u.last_name','u.nickname','u.photo','g.status','g.approved','g.id as game_id')
        ->join('users as u','i.invited_user_id','=','u.id')
        ->join('games as g','g.id','=','i.invited_user_game')
        ->where('i.user_id','=',Auth::id())
        ->get();

        return response()->json($users);
    }

    public function recovery_password(Request $request){
        $data = [];
        $code = rand(100000,999999);
        $email = $request->input('email');
        $user = User::where('email',$email)->first();

        if(!empty($user)){
            $user_active = $user->id;
            $user->remember_token = $code;
            $user->email_verified_at = date('Y-m-d H:m:s');

            if($user->update()){
                 $data_email = ['code' => $code];
                 Mail::to($email)->send(new RecoveryPassword($data_email));
                 $data = ['error' => 0,'message' => 'Codigo enviado con Exito!!','user_active' => $user_active];
            }else{
                $data = ['error' => 1,'message' => 'Ocurrio un error al enviar el codigo al Correo!!'];
            }
        }else{
            $data = ['error' => 2,'message' => 'El correo que igreso no pertence a ningun usuario Registrado!!'];
        }
        
        return response()->json($data);
    }

    public function validate_code(Request $request){
        $data = [];
        $code = $request->input('code');

        $user = User::where('remember_token',$code)->firstOrFail();

        if(!empty($user)){
            $data = ['error' => 0, 'message' => 'Codigo Validado con Exito!!'];
        }else{
            $data = ['error' => 1, 'message' => 'El codigo que ingreso no existe, por favor verifique su correo o mande Otro!!'];
        }


        return response()->json($data);
    }

    public function change_password(Request $request){
        $data = [];
        $id = $request->input('id');
        $password = $request->input('password');

        $user = User::findorfail($id);

        $user->password = bcrypt($password);

        if($user->update()){
            $data = ['error' => 0, 'message' => 'Contraseña Actualizada con Exito!!'];
        }else{
            $data = ['error' => 1, 'message' => 'Error al tratar de Actualizar la Contraseña!!'];
        }

        return response()->json($data);
    }

    public function send_message(Request $request, $receiver_id, $msj){
        $data = [];
        $user = $request->user();
        $mss = new Message();
        $mss->sender_id = $user->id;
        $mss->receiver_id = $receiver_id;
        $mss->message = $msj;

        if($mss->save()){
            $data = ['error' => 0, 'message' => 'Mensaje Enviado con Exito!!'];
        }else{
            $data = ['error' => 1, 'message' => 'Error al tratar de envar el Mensaje!!'];
        }

        return response()->json($data);
    }

    public function get_notifications(Request $request){
        $data = [];

        $mss = Message::where('receiver_id',$request->user()->id)->where('status','no-read')->get();

        if(!empty($mss)){
            $data = ['error' => 0, 'mensajes' => $mss->count()];
        }else{
            $data = ['error' => 0, 'mensajes' => 0];
        }

        return response()->json($data);
    }

    public function list_notifications(Request $request){
        $data = [];
        $notifications = [];
        $mss = Message::where('receiver_id',$request->user()->id)->orderBy('created_at','DESC')->get();

        if(!empty($mss)){
            foreach($mss as $ms){
                $push = [
                    'sender' => $ms->sender->name.' '.$ms->sender->last_name,
                    'message' => $ms->message
                ];
                $this->change_status_notifications($ms->id);
                array_push($notifications, $push);
            }

            $data = ['error' => 0, 'message' => 'Listando Notificaciones!!','notifications' => $notifications];
        }else{
            $data = ['error' => 1, 'message' => 'Error al listar Notificaciones'];
        }  

        return response()->json($data);
    }

    public function change_status_notifications($id){
        $message = Message::findorfail($id);
        $message->status = 'read';
        $message->update();
    }
    
}
