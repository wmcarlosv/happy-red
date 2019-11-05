<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Invitation;
use App\User;
use App\Game;
use Log;
use Carbon\Carbon;


class ObjectiveFirst extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'objective:first';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check is player complete the 1st game Objetive ';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
        $invitations = Invitation::select('invitations.*','games.approved')
        ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
        ->where('invitations.status','=',1)
        ->where('games.status','=',1)
        ->where('games.approved','=',1)
        ->orderBy('invitations.created_at', 'desc')
        ->get();
        
        foreach ($invitations as $key => $invitation) {
            dump("(".$invitation->id.")------------------");
            $level_1_game = Invitation::select('invitations.*')
            ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
            ->where('invitations.status','=',1)
            ->where('games.status','=',1)
            ->where('games.approved','=',1)
            ->where('invitations.level_1','=',$invitation->invited_user_id)
            ->where('invitations.status','=',1)
            ->count();
            dump('Invitaciones:'.$level_1_game);
            if($level_1_game == config('api.max_players_level')){
                //Game complete
                dump("Game complete");
                \Mail::send([], [], function ($message) use ($invitation) {
                    $html = '<h1>Completo, '.config('api.max_players_level').' invitaciones completadas!</h1>';
                    $html .= '<h2>Usuario:</h2>';
                    $html .= '<pre>'.json_encode(User::find($invitation->invited_user_id), JSON_PRETTY_PRINT).'</pre>';
                    $html .= '<h2>Invitación:</h2>';
                    $html .= '<pre>'.json_encode($invitation, JSON_PRETTY_PRINT).'</pre>';
                    $message->subject('Game complete');
                    $message->to(config('api.admin_mail'));
                    $message->setBody($html, 'text/html'); // for HTML rich messages
                });
                $invitation->status = config('api.status.win_1');
                $invitation->update();

            }elseif($level_1_game > config('api.max_players_level')){
                // Game Error
                dump("Game Error");
                \Mail::send([], [], function ($message) use ($invitation) {
                    $html = '<h1>Error, mas de '.config('api.max_players_level').' invitaciones aceptadas!</h1>';
                    $html .= '<h2>Usuario:</h2>';
                    $html .= '<pre>'.json_encode(User::find($invitation->invited_user_id), JSON_PRETTY_PRINT).'</pre>';
                    $html .= '<h2>Invitación:</h2>';
                    $html .= '<pre>'.json_encode($invitation, JSON_PRETTY_PRINT).'</pre>';
                    $message->subject('Game Error');
                    $message->to(config('api.admin_mail'));
                    $message->setBody($html, 'text/html'); // for HTML rich messages
                });
                $invitation->status = config('api.status.win_1');
                $invitation->update();
                Log::error('Error, mas de '.config('api.max_players_level').' invitaciones aceptadas '.$invitation);
           
            }else{
                $to = Carbon::createFromFormat('Y-m-d H:s:i', $invitation->created_at);
                $from = Carbon::now('America/Argentina/Buenos_Aires');
                $diff_in_days = $to->diffInDays($from);


                //Game over
                dump('Dias:'. $diff_in_days);
                if($diff_in_days > config('api.max_diff_days')){
                    dump("Game over");
                    \Mail::send([], [], function ($message) use ($invitation) {
                        $html = '<h1>Game over, supero los '.config('api.max_diff_days').' dias!</h1>';
                        $html .= '<h2>Usuario:</h2>';
                        $html .= '<pre>'.json_encode(User::find($invitation->invited_user_id), JSON_PRETTY_PRINT).'</pre>';
                        $html .= '<h2>Invitación:</h2>';
                        $html .= '<pre>'.json_encode($invitation, JSON_PRETTY_PRINT).'</pre>';
                        $message->subject('Game over');
                        $message->to(config('api.admin_mail'));
                        $message->setBody($html, 'text/html'); // for HTML rich messages
                    });

                    //Find a Parent to notify
                    if($invitation->user_id) {
                        $parent = Invitation::select('invitations.*','games.approved')
                        ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
                        ->where('invitations.invited_user_id','=',$invitation->user_id)
                        ->where('invitations.status','=',2)
                        ->where('games.status','=',1)
                        ->where('games.approved','=',1)
                        ->orderBy('invitations.created_at', 'desc')
                        ->get();

                        if($parent){
                            dump("Notify Parent");
                            $user = User::find(user_id);
                            \Mail::send([], [], function ($message) use ($user) {
                                $html = '<h1>Necesitas buscar un nuevo participantes</h1>';
                                $html .= 'Uno de tus participante no logro completar el primer objetivo.';
                                $html .= 'Por favor encuentra un nuevo participante.';
                                $message->subject('Happy-Red');
                                $message->to($user->email);
                                $message->setBody($html, 'text/html'); // for HTML rich messages
                            });
                        }
                    }

                    //Change status to 0
                    $invitation->status = config('api.status.game_over');
                    $invitation->update();
                    //Payment to 0
                    $game = Game::find($invitation->invited_user_game);
                    $game->status = config('api.status.game_over');
                    //LOG
                    Log::error('Game over, supero los '.config('api.max_diff_days').' dias'.$invitation);
                }
            }
        }

    }
}
