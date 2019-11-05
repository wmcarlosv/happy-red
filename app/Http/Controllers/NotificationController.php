<?php

namespace App\Http\Controllers;

use App\Notification;
use App\User;
use Illuminate\Http\Request;
use Auth;

class NotificationController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    /**
     *
     */
    public function getMessages()
    {
        $user_id = Auth::id() ? Auth::id() : 0;
        $send_messages = Notification::where('user_sender',$user_id)
        ->orderBy('created_at','desc')->get();
        $received_messages = Notification::where('user_addressee',$user_id)
        ->orderBy('created_at','desc')->get();
        $messages = [
            'send_messages' => $send_messages,
            'received_messages' => $received_messages,
        ];
        return response()->json($messages, 200);
    }
    
    /**
     *
     */
    public function sentMessage(Request $request)
    {
        $request->validate([
            'user_addressee' => 'required',
            'message' => 'required|string',
        ]);
        $user_sender = Auth::id() ? Auth::id() : 0;
        $user_addressee = User::find($request['user_addressee']);
        if(!$user_addressee){
            return response()->json('The user_addressee does not exist', 400);
        }

        $notification = new Notification();
        $notification->user_sender = $user_sender;
        $notification->user_addressee = $request['user_addressee'];
        $notification->message = $request['message'];
        $notification->save();
        return response()->json($notification, 200);
    }

    /**
     *
     */
    public function readMessage(Request $request)
    {
        $request->validate([
            'id' => 'required',
        ]);

        $notification = Notification::find($request['id']);
        $notification->read = TRUE;
        $notification->update();
        return response()->json($notification, 200);
    }

}
