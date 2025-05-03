<?php

namespace App\Http\Controllers;

use App\Models\ElectionList;
use App\Models\Candidate;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ElectionListController extends Controller
{
    /**
     * Display a listing of elections.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $elections = ElectionList::all();

            // Calculate and set counts and update status for each election
            foreach ($elections as $election) {
                $election->candidate_count = Candidate::where('election_id', $election->id)->count();
                $election->voter_count = Vote::where('election_id', $election->id)
                    ->distinct('user_id')->count('user_id');

                // Create a formatted copy for response
                $formattedElection = $election->toArray();
                $formattedElection['election_date'] = Carbon::parse($election->election_date)
                    ->translatedFormat('l, d F Y');
                $formattedElections[] = $formattedElection;
            }
            return response()->json(['data' => $formattedElections]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch elections',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created election in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string',
            'election_date' => 'required|date',
        ]);

        // Check if there's already an election scheduled on this date
        $existingElection = ElectionList::whereDate('election_date', $validated['election_date'])->first();
        if ($existingElection) {
            return response()->json([
                'message' => 'An election is already scheduled on this date'
            ], 400);
        }

        // Set counts to 0 by default
        $validated['candidate_count'] = 0;
        $validated['voter_count'] = 0;

        // Set initial status based on comparison with current date
        $today = Carbon::today();
        $electionDate = Carbon::parse($validated['election_date']);

        if ($today->isSameDay($electionDate)) {
            $validated['status'] = 'active';
        } elseif ($today->isAfter($electionDate)) {
            $validated['status'] = 'closed';
        } else {
            $validated['status'] = 'upcoming';
        }

        try {
            $election = ElectionList::create($validated);

            // Format date for response
            $formattedElection = $election->toArray();
            $formattedElection['election_date'] = Carbon::parse($election->election_date)
                ->translatedFormat('l, d F Y');

            return response()->json([
                'message' => 'Election created successfully',
                'data' => $formattedElection
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create election',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified election.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $election = ElectionList::find($id);

        if (!$election) {
            return response()->json([
                'message' => 'Election not found'
            ], 404);
        }

        // Calculate and set counts
        $election->candidate_count = Candidate::where('election_id', $election->id)->count();
        $election->voter_count = Vote::where('election_id', $election->id)
            ->distinct('user_id')->count('user_id');

        // Get candidates with their vote counts
        $candidates = Candidate::where('election_id', $election->id)->get();
        $candidatesWithVotes = [];

        foreach ($candidates as $candidate) {
            $voteCount = Vote::where('candidate_id', $candidate->id)->count();
            $candidatesWithVotes[] = [
                'id' => $candidate->id,
                'number' => $candidate->number,
                'name' => $candidate->name,
                'vote_count' => $voteCount
            ];
        }

        // Format date for response
        $formattedElection = $election->toArray();
        $formattedElection['election_date'] = Carbon::parse($election->election_date)
            ->translatedFormat('l, d F Y');
        $formattedElection['candidates'] = $candidatesWithVotes;

        return response()->json(['data' => $formattedElection]);
    }

    /**
     * Update the specified election in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $election = ElectionList::find($id);

        if (!$election) {
            return response()->json([
                'message' => 'Election not found'
            ], 404);
        }

        $validated = $request->validate([
            'title' => 'sometimes|required|string',
            'election_date' => 'sometimes|required|date',
        ]);

        // Check if trying to update to a date that already has an election
        if (isset($validated['election_date'])) {
            $existingElection = ElectionList::where('id', '!=', $id)
                ->whereDate('election_date', $validated['election_date'])
                ->first();

            if ($existingElection) {
                return response()->json([
                    'message' => 'Another election is already scheduled for this date'
                ], 400);
            }
        }

        try {
            $election->update($validated);

            // Recalculate counts
            $election->candidate_count = Candidate::where('election_id', $election->id)->count();
            $election->voter_count = Vote::where('election_id', $election->id)
                ->distinct('user_id')->count('user_id');

            // Format date for response
            $formattedElection = $election->toArray();
            $formattedElection['election_date'] = Carbon::parse($election->election_date)
                ->translatedFormat('l, d F Y');

            return response()->json([
                'message' => 'Election updated successfully',
                'data' => $formattedElection
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update election',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified election from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $election = ElectionList::find($id);

        if (!$election) {
            return response()->json([
                'message' => 'Election not found'
            ], 404);
        }

        try {
            // Option 1: Delete related records first (if you want cascade delete)
            // Candidate::where('election_id', $election->id)->delete();
            // Vote::where('election_id', $election->id)->delete();

            // Option 2: Check if there are related records and prevent deletion
            $candidateCount = Candidate::where('election_id', $election->id)->count();
            $voteCount = Vote::where('election_id', $election->id)->count();

            if ($candidateCount > 0 || $voteCount > 0) {
                return response()->json([
                    'message' => 'Cannot delete election with associated candidates or votes'
                ], 400);
            }

            $election->delete();

            return response()->json([
                'message' => 'Election deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete election',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the currently active election.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function activeElection()
    {
        try {
            $activeElection = ElectionList::where('status', 'active')->first(['id', 'title', 'election_date', 'status']);

            if (!$activeElection) {
                return response()->json([
                    'message' => 'No active election found'
                ], 404);
            }

            return response()->json(['data' => $activeElection]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch active election',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
