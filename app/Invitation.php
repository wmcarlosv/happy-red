<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Invitation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'invited_user_id',
        'level_1',
        'level_2',
        'level_3',
        'level_4',
        'level_5',
        'status',
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
    protected $table = 'invitations';
    
    /**
     * Get the game record associated with the invitation.
     */
    public function game()
    {
        return $this->hasOne('App\Game', 'id', 'invited_user_game');
    }

}
