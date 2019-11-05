<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_sender',
        'user_addressee',
        'read',
        'message',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'notifications';


    /**
    * 
    */
    public function user_sender()
    {
        return $this->belongsTo('App\User');
    }

     /**
    * 
    */
    public function user_addressee()
    {
        return $this->belongsTo('App\User');
    }
}
