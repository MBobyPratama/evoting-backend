<?php

namespace App\Http\Controllers;

use App\Models\ElectionList;
use App\Models\Candidate;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    // Set SSE-specific headers
    header('Content-Type: text/event-stream');
    header("Access-Control-Allow-Origin: " . env('WEBSITE_URL')); //nanti ganti sesuai domain nya 
    header("Access-Control-Allow-Headers: *");
    header('Cache-Control: no-cache');
    header('Connection: keep-alive');
    header('X-Accel-Buffering: no'); // Disable Nginx buffering

    // Disable PHP output buffering
    while (ob_get_level() > 0) ob_end_flush();
    ob_implicit_flush(true);

    // Optional: Set retry delay (client will retry after 5 seconds if disconnected)
    echo "retry: 5000\n\n";

    // Keep the script running
    set_time_limit(0);

    while (true) {
        // Break the loop if client disconnects
        if (connection_aborted()) {
            break;
        }

        // Get data and send it
        $data = [
             'data' => $this->getElectionData($electionId)
        ];

        echo "data: " . json_encode($data) . "\n\n";
        ob_flush();
        flush();

        // Wait before sending the next update
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
