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

        // Format date for response
        $formattedElection = $election->toArray();
        $formattedElection['election_date'] = Carbon::parse($election->election_date)
            ->translatedFormat('l, d F Y');

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
            'title' => 'sometimes|required|string|max:255',
            'election_date' => 'sometimes|required|date',
        ]);

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
}
