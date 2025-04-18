<?php

namespace App\Http\Controllers;

use App\Models\ElectionList;
use App\Models\Candidate;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * Stream real-time election data using Server-Sent Events
     *
     * @param int $electionId
     * @return void
     */
    public function stream($electionId)
    {
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('X-Accel-Buffering: no'); // Disable buffering for Nginx

        // Set time limit to unlimited for long-running connection
        set_time_limit(0);

        // Send data every 2 seconds
        while (true) {
            $eventData = [
                'data' => $this->getElectionData($electionId)
            ];

            echo "data: " . json_encode($eventData) . "\n\n";
            ob_flush();
            flush();

            // Sleep for 2 seconds before sending next update
            sleep(2);
        }
    }

    /**
     * Get election data with real-time vote counts
     *
     * @param int $electionId
     * @return array
     */
    private function getElectionData($electionId)
    {
        // Get the election
        $election = ElectionList::find($electionId);

        if (!$election) {
            return ['error' => 'Election not found'];
        }

        // Get all candidates for this election with their vote counts
        $candidates = Candidate::where('election_id', $electionId)->get();
        $candidatesData = [];

        foreach ($candidates as $candidate) {
            // Count votes for this candidate
            $voteCount = Vote::where('candidate_id', $candidate->id)->count();

            $candidatesData[] = [
                'id' => $candidate->id,
                'name' => $candidate->name,
                'number' => $candidate->number,
                'votes' => $voteCount
            ];
        }

        // Count total voters for this election (distinct users who voted)
        $voterCount = Vote::where('election_id', $electionId)
            ->distinct('user_id')
            ->count('user_id');

        // Format election date
        $formattedDate = Carbon::parse($election->election_date)
            ->translatedFormat('l, d F Y');

        // Compile the final data structure
        return [
            'election_id' => $election->id,
            'title' => $election->title,
            'election_date' => $formattedDate,
            'status' => $election->status,
            'voter_count' => $voterCount,
            'candidates' => $candidatesData
        ];
    }
}
