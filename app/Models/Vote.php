<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vote extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'candidate_id',
        'user_id',
        'election_id',
    ];

    /**
     * Get the user who cast this vote.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the candidate this vote is for.
     */
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    /**
     * Get the election this vote belongs to.
     */
    public function election()
    {
        return $this->belongsTo(ElectionList::class, 'election_id');
    }
}
