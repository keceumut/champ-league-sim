<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matches extends Model
{
    protected $fillable = ['week', 'home_team_id', 'guest_team_id', 'home_score', 'guest_score', 'is_played'];

    public function homeTeam()
    {
        return $this->belongsTo(Team::class, 'home_team_id');
    }

    public function guestTeam()
    {
        return $this->belongsTo(Team::class, 'guest_team_id');
    }
}
