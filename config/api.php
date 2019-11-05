<?php

return [
    'admin_mail' => env('ADMIN_MAIL','admin@general.org'),
    'max_players_level' => env('MAX_PLAYERS_LEVELS',3),
    'levels' => env('LEVELS',5),
    'max_diff_days' => env('MAX_DIFF_DAYS',10),
    'status' => [
        'game_over' => 0,
        'playing' => 1,
        'win_1' => 2,
        'win_2' => 3,
    ],
    'inscription' => env('INSCRIPTION',1000),
    'prize' => env('INSCRIPTION',1000) * 81,
    //'total_users' => _tu( env('MAX_PLAYERS_LEVELS',3), env('LEVELS',5) ),
];


/*function _tu($max_players_level, $levels){
    $total = 0;
    for ($i=1; $i <=$levels ; $i++) { 
        $total += pow ( $max_players_level, $i );
    }
    return $total;
}*/