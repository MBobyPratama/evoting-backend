<?php

namespace App\Http\Controllers;

use App\Models\Vote;
use App\Models\Candidate;
use App\Models\ElectionList;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoteController extends Controller
{
    /**
     * Store a newly created vote in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
        ]);

        // Get the authenticated user
        $user = Auth::user();

        // Get the candidate
        $candidate = Candidate::find($validated['candidate_id']);
        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate not found'
            ], 404);
        }

        // Get the election
        $election = ElectionList::find($candidate->election_id);
        if (!$election) {
            return response()->json([
                'message' => 'Election not found'
            ], 404);
        }

        // Check if the election is active
        if ($election->status !== 'active') {
            return response()->json([
                'message' => 'Voting is only allowed for active elections'
            ], 400);
        }

        // Check if the user already voted in this election
        $existingVote = Vote::where('user_id', $user->id)
            ->where('election_id', $candidate->election_id)
            ->first();

        if ($existingVote) {
            return response()->json([
                'message' => 'You have already voted in this election',
            ], 400);
        }

        try {
            // Create the vote
            $vote = Vote::create([
                'user_id' => $user->id,
                'candidate_id' => $validated['candidate_id'],
                'election_id' => $candidate->election_id
            ]);

            return response()->json([
                'message' => 'Vote recorded successfully',
                'data' => $vote
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to record vote',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if the authenticated user has voted in a specific election.
     *
     * @param int $electionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkVote($electionId)
    {
        $user = Auth::user();

        // Verify election exists
        $election = ElectionList::find($electionId);
        if (!$election) {
            return response()->json([
                'message' => 'Election not found'
            ], 404);
        }

        $vote = Vote::where('user_id', $user->id)
            ->where('election_id', $electionId)
            ->first();

        if ($vote) {
            $candidate = Candidate::find($vote->candidate_id);

            // Hash the candidate number for privacy
            $hashedNumber = hash('sha256', $candidate->number . config('app.key'));

            return response()->json([
                'has_voted' => true,
                'candidate' => [
                    'id' => $candidate->id,
                    'name' => $candidate->name,
                    'number' => $hashedNumber,
                ]
            ]);
        }

        return response()->json([
            'has_voted' => false
        ]);
    }

    /**
     * Get voting results for a specific election.
     *
     * @param int $electionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getResults($electionId)
    {
        // Check if the election exists
        $election = ElectionList::find($electionId);
        if (!$election) {
            return response()->json([
                'message' => 'Election not found'
            ], 404);
        }

        // Only allow viewing results for closed elections
        if ($election->status !== 'closed') {
            return response()->json([
                'message' => 'Results are only available for closed elections'
            ], 403);
        }

        // Get candidates for this election
        $candidates = Candidate::where('election_id', $electionId)->get();

        $results = [];
        $totalVotes = Vote::where('election_id', $electionId)->count();

        foreach ($candidates as $candidate) {
            $voteCount = Vote::where('candidate_id', $candidate->id)->count();
            $percentage = $totalVotes > 0 ? round(($voteCount / $totalVotes) * 100, 2) : 0;

            $results[] = [
                'candidate_id' => $candidate->id,
                'candidate_number' => $candidate->number,
                'candidate_name' => $candidate->name,
                'votes' => $voteCount,
                'percentage' => $percentage
            ];
        }

        // Sort by votes (highest first)
        usort($results, function ($a, $b) {
            return $b['votes'] - $a['votes'];
        });

        return response()->json([
            'election_id' => $electionId,
            'election_title' => $election->title,
            'total_votes' => $totalVotes,
            'results' => $results
        ]);
    }
}
