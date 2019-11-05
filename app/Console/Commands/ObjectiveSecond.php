<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Invitation;
use App\User;
use Log;
use Carbon\Carbon;

class ObjectiveSecond extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'objective:second';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check is player complete the 2st game Objetive';

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
        //All invitation whit 1st Objetive Complete
        $invitations = Invitation::select('invitations.*','games.approved')
        ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
        ->where('invitations.status','=',2)
        ->where('games.status','=',1)
        ->where('games.approved','=',1)
        ->orderBy('invitations.created_at', 'desc')
        ->get();
        
        foreach ($invitations as $key => $invitation) {
            dump("(".$invitation->id.")------------------");
            $level_5_game = Invitation::select('invitations.*')
            ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
            ->where('invitations.status','=',1)
            ->where('games.status','=',1)
            ->where('games.approved','=',1)
            ->where('invitations.level_5','=',$invitation->invited_user_id)
            ->where('invitations.status','=',1)
            ->count();
            dump('Invitaciones level 5: '.$level_5_game);
            
            //Check level 5
            if($level_5_game == pow(config('api.max_players_level'),5)){
                //Check level 4
                $level_4_game = Invitation::select('invitations.*')
                ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
                ->where('invitations.status','=',1)
                ->where('games.status','=',1)
                ->where('games.approved','=',1)
                ->where('invitations.level_4','=',$invitation->invited_user_id)
                ->where('invitations.status','=',1)
                ->count();
                //Check level 3
                $level_3_game = Invitation::select('invitations.*')
                ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
                ->where('invitations.status','=',1)
                ->where('games.status','=',1)
                ->where('games.approved','=',1)
                ->where('invitations.level_3','=',$invitation->invited_user_id)
                ->where('invitations.status','=',1)
                ->count();
                //Check level 2
                $level_2_game = Invitation::select('invitations.*')
                ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
                ->where('invitations.status','=',1)
                ->where('games.status','=',1)
                ->where('games.approved','=',1)
                ->where('invitations.level_2','=',$invitation->invited_user_id)
                ->where('invitations.status','=',1)
                ->count();
                //Check level 1
                $level_1_game = Invitation::select('invitations.*')
                ->leftJoin('games', 'games.id', '=', 'invitations.invited_user_game')
                ->where('invitations.status','=',1)
                ->where('games.status','=',1)
                ->where('games.approved','=',1)
                ->where('invitations.level_1','=',$invitation->invited_user_id)
                ->where('invitations.status','=',1)
                ->count();

                //Verify all tree
                if( 
                    $level_4_game == pow(config('api.max_players_level'),4) &&
                    $level_3_game == pow(config('api.max_players_level'),3) &&
                    $level_2_game == pow(config('api.max_players_level'),2) &&
                    $level_1_game == pow(config('api.max_players_level'),1) 
                ){
                    //Game complete
                    dump("Game Happy complete");
                    dump('Invitaciones Totales:'.pow(config('api.max_players_level'),5));
                    \Mail::send([], [], function ($message) use ($invitation) {
                        $html = '<h1>Completo todos los niveles!</h1>';
                        $html .= '<h2>Usuario:</h2>';
                        $html .= '<pre>'.json_encode(User::find($invitation->invited_user_id), JSON_PRETTY_PRINT).'</pre>';
                        $html .= '<h2>Invitación:</h2>';
                        $html .= '<pre>'.json_encode($invitation, JSON_PRETTY_PRINT).'</pre>';
                        $message->subject('Game Happy complete');
                        $message->to(config('api.admin_mail'));
                        $message->setBody($html, 'text/html'); // for HTML rich messages
                    });
                    $invitation->status = config('api.status.win_2');
                    $invitation->update();

                    //Payment to Win
                    $game = Game::find($invitation->invited_user_game);
                    $game->status = config('api.status.win_2');
                    
                }else{
                    Log::warning('Juego Nivel 5 Completado pero falta hijos.[1=>'.$level_1_game.' ,2=>'.$level_2_game.' ,3=>'.$level_3_game.' ,4=>'.$level_4_game.' ,5=>'.$level_5_game.']');
                }
            }
        }
    }
}
