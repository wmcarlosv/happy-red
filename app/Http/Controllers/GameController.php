<?php

namespace App\Http\Controllers;

use App\Invitation;
use App\User;
use App\Game;
use Illuminate\Http\Request;
use Image;
use Storage;
use Auth;
use DB;

class GameController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function gameUser()
    {
        //deprecated
        return response()->json('deprecated', 200);return
        // Pay Game
        $user_id = Auth::id() ? Auth::id() : 0;
        $game = Game::where('user_id', '=', $user_id)
                ->where('status', '=', 1)
                ->where('approved', '=', 1)
                ->first();

        return response()->json($game, 200);
    }

    public function gamePay(Request $request)
    {

        $request->validate([
            'payment' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'id_game' => 'required',
        ]);

        $user_id = Auth::id() ? Auth::id() : 0;
        $game = Game::find($request->id_game);

        // Verify payment and status
        if($game){
            if($game->approved > 0){
                return response()->json('Imposible enviar. El pago ya se encuentra aprobado', 403);
            }
            if($game->status > 0){
                return response()->json('Imposible enviar. El juego ya se encuentra activo', 403);
            }
        }else{
            return response()->json('Game Id inexistente', 403);
        }

        $user = Auth::user();
        if ($request->hasFile('payment') && isset($game->id)) {
            $image      = $request->file('payment');
            $fileName   = time() . '_' . $user_id  . '.' .$image->getClientOriginalExtension();
            $img = Image::make($image->getRealPath());
            /* FOR RESIZE
            $img->resize(120, 120, function ($constraint) {
                $constraint->aspectRatio();                 
            });
            */
            $img->stream(); // <-- Key point
            Storage::disk('public')->put('payment/'.$user_id.'/'.$fileName, $img, 'public');

            $game->user_id = $user_id;
            $game->payment = $fileName;
            $game->update();

            // Mail payment notifications
            \Mail::send([], [], function ($message) use ($img,$fileName,$user) {
                $html = '<h1>'.$user->name.' '.$user->last_name.'</h1>';
                $html .= '<pre>'.json_encode($user, JSON_PRETTY_PRINT).'</pre>';
                $message->subject('Nuevo pago');
                $message->to(config('api.admin_mail'));
                $message->setBody($html, 'text/html'); // for HTML rich messages
                $message->attachData($img, $fileName);
              });

            return response()->json('OK', 200);
        }
        return response()->json('Game User Error', 403);
    }
    

    public function gameStart(Request $request)
    {
        $pre_invitation = null;
        $user_id = null;
        $current_user = Auth::id() ? Auth::id() : 0;

        // If code exist 
        $code = $request->code;
        if($code != ''){
            $user = User::where('code', '=', $code)->first();
            // Check Code
            if(!$user){
                return response()->json('The Code is not valid.', 400);
            }
            $user_id = $user->id;

            // Check is game of CODE is allow
            if($user_id){
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
                    return response()->json('The code is pending to pay.', 400);
                }
            }else{
                //First User No invitation
                if($user_id != 2){
                    return response()->json('The code is not in a active game.', 400);
                }
            }

            $level_1_game = Invitation::select('invitations.*')
            ->where('invitations.level_1','=',$user_id)
            ->where('invitations.status','=',1)
            ->count();

            // Check is user of CODE is available by limit
            if($level_1_game >= config('api.max_players_level')){
                return response()->json('The code is full of players.', 400);
            }
            
        }

        // Check if current playing and 1st objetive
        $current_game = Invitation ::where('invited_user_id','=',$current_user)
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
        return response()->json($invitation, 200);
    }


    public function gameData(Request $request)
    {
        $user_id = Auth::id() ? Auth::id() : 0;
        $invitations = Invitation::select('invitations.*','games.approved');
        $invitations->where('invited_user_id','=',$user_id);
        $invitations->where('invitations.status','=', $request->status ? $request->status : 1);
        $invitations->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game');
        if($request->invited_user_game){
            $invitations->where('invited_user_game','=', $request->invited_user_game);
        }   
        $origin_data = $invitations->get();
        $childs_data = [];

        if(count($origin_data) == 0){
            $levels = Invitation::select('invitations.*','games.approved');
            $levels->where('invitations.status','=',1);
            $levels->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game');
            $levels->where('level_1','=', $user_id);
            array_push($childs_data, $levels->get());
        }else{
            foreach ($origin_data as $key => $value) {
                $levels = Invitation::select('invitations.*','games.approved');
                $levels->where('invitations.status','=',1);
                $levels->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game');
                $levels->where('level_1','=', $user_id);
                array_push($childs_data, $levels->get());
            }
        }
        $game_data = [
            'origin_data' => $origin_data,
            'childs_data' => $childs_data,
        ];

        return response()->json($game_data, 200);
    }


    public function gameDataLevel(Request $request)
    {
        $level = $request->level ? $request->level : 0;
        if($level > 0 && $level < 6){
            $user_id = Auth::id() ? Auth::id() : 0;
            $invitations = Invitation::select('invitations.*','games.approved');
            $invitations->where('invitations.status','=',1);
            $invitations->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game');
            $invitations->where('level_'.$level,'=', $user_id);
            $levels =$invitations->get();

            return response()->json($levels, 200);
        }
        return response()->json('Wrong level', 400);
    }

    public function gameDataTree(Request $request)
    {
        $user_id = Auth::id() ? Auth::id() : 0;
        $invitations = Invitation::select('invitations.*','games.approved');
        $invitations->where('invitations.user_id','=',$user_id);//$user_id);
        $invitations->where('invitations.status','=', 1);
        $invitations->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game');
        
        $data = $invitations->first();
        $tree = [];
        if($data){
            $tree = [
                'level_1' => ['user' => $data->level_1, 'total' => $this->gameUserMoney($data->level_1)],
                'level_2' => ['user' => $data->level_2, 'total' => $this->gameUserMoney($data->level_2)],
                'level_3' => ['user' => $data->level_3, 'total' => $this->gameUserMoney($data->level_3)],
                'level_4' => ['user' => $data->level_4, 'total' => $this->gameUserMoney($data->level_4)],
                'level_5' => ['user' => $data->level_5, 'total' => $this->gameUserMoney($data->level_5)],
            ];
        }

        return response()->json($tree, 200);
    }

    private function gameUserMoney($user_id)
    {
        $level_1 = $this->gameUserLevelCount($user_id,1);
        $level_2 = $this->gameUserLevelCount($user_id,2);
        $level_3 = $this->gameUserLevelCount($user_id,3);
        $level_4 = $this->gameUserLevelCount($user_id,4);
        $level_5 = $this->gameUserLevelCount($user_id,5);
        $total = $level_1 + $level_2 + $level_3 + $level_4 + $level_5;
        $acumulado = 0;
        if($total){
            $price = config('api.prize');
            $total_users = config('api.total_users');            
            $acumulado = round(($price / $total_users) * $total , 2);
        }
        return $acumulado;
    }

    private function gameUserLevelCount($user_id,$level){
        if(!$user_id){
            return 0;
        }
        $invitations = Invitation::select('invitations.*','games.approved');
        $invitations->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game');
        $invitations->where('games.status','=',1);
        $invitations->where('games.approved','=',1);
        $invitations->where('invitations.level_'.$level,'=',$user_id);
        $invitations->where('invitations.status','=',1);
        return $invitations->count();
    }

    public function getDataByLevel($level){
        $data = [];
        $cont = 0;
        $users = DB::table('invitations')
        ->select('users.name','users.last_name','users.photo','users.id')
        ->join('users','users.id','=','invitations.invited_user_id')
        ->join('games','games.user_id','=','users.id')
        ->where('invitations.'.$level,'=',Auth::id())
        ->where('games.status','=',1)
        ->where('games.approved','=',1)
        ->get();

        for($i=0; $i < count($users); $i++){
            $data[$cont]['name'] = $users[$i]->name;
            $data[$cont]['last_name'] = $users[$i]->last_name;
            $data[$cont]['photo'] = $users[$i]->photo;
            $data[$cont]['custom_html'] = $this->generateInvitedHtml($users[$i]->id);
            $data[$cont]['receiver_id'] = $users[$i]->id;
            $cont++;
        }

        return response()->json($data, 200);
    }

    public function generateInvitedHtml($user_id){
        $html = "";
        $limit = 3;
        $counter = 0;
        $users = DB::table('invitations')
        ->select('users.name','users.last_name','users.photo','games.status','games.approved')
        ->join('users','users.id','=','invitations.invited_user_id')
        ->join('games','games.user_id','=','users.id')
        ->where('invitations.level_1','=',$user_id)
        ->get();

        for($i=0; $i < count($users); $i++){

            if($users[$i]->status == 1 && $users[$i]->approved == 1){
                $html.='<ion-icon slot="end" name="md-contact" style="color:green; width:24px; height:24px;"></ion-icon>';
            }

            if($users[$i]->status != 1 || $users[$i]->approved != 1){
                $html.='<ion-icon slot="end" name="md-contact" style="color:yellow; width:24px; height:24px;"></ion-icon>';
            }

            $counter++;
        }

        $limit = ($limit - $counter);
        for($i=0; $i < $limit; $i++){
            $html.='<ion-icon slot="end" name="md-contact" style="color:red; width:24px; height:24px;"></ion-icon>';
        }

        return $html;
    }

    public function validateEndGame(){

        $total = 0;

        for($i = 1; $i <= 5; $i++){
            $users = DB::table('invitations')
                ->select('users.name','users.last_name','users.photo','users.id')
                ->join('users','users.id','=','invitations.invited_user_id')
                ->join('games','games.user_id','=','users.id')
                ->where('invitations.level_'.$i,'=',Auth::id())
                ->where('games.status','=',1)
                ->where('games.approved','=',1)
                ->get();

            $total+=$users->count();
        }

        return response()->json(['total' => $total]);
        
    }

    public function firstGame($id){
        $game = Game::findorfail($id);
        $game->status = 1;
        $game->approved = 1;

        if($game->update()){
            $data = ['message','Registro Pagado con Exito!!'];
        }else{
            $data = ['message' => 'Error al intenta pagar este Registro!!'];
        }

        return response()->json($data);
    }

}