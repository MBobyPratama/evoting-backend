<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ElectionList extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'election_date',
        'status',
        'candidate_count',
        'voter_count',
    ];
}
