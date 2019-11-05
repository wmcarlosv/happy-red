<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'status',
        'description',
        'payment',
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
    protected $table = 'games';


    /**
    * Get the user that owns the phone.
    */
    public function user()
    {
        return $this->belongsTo('App\Invitation', 'user_id');
    }
}
