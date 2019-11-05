<?php

namespace App\Http\Controllers;

use App\Invitation;
use App\User;
use Illuminate\Http\Request;
use Auth;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function userGame()
    {
        // Juego Pago
        

/*
        $user_id = Auth::id() ? Auth::id() : 0;
        $invitation = Invitation::
        where(function ($query) use ($user_id) {
            $query->where('user_id', '=', $user_id)
                  ->orWhere('invited_user_id', '=', $user_id);
        })
        ->get();
*/      
        // Nivel 1

        return response()->json($invitation, 200);
    }


    public function UserLevel($level)
    {
        // Juego Pago
        
        // Nivel X

    }

    public function UserHistory()
    {
        // Juegos
        
        // Todos Nivel

    }
}