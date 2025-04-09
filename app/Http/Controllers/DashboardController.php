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
     * Stream election details with real-time updates using Server-Sent Events
     * 
     * @param int $id Election ID
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function sse_dashboard_detail($id)
    {
        // Find the election
        $election = ElectionList::find($id);
        if (!$election) {
            return response()->json([
                'message' => 'Election not found'
            ], 404);
        }

        // Set SSE response headers
        $response = response()->stream(function () use ($election) {
            // Set initial time for comparison
            $lastUpdate = now();

            // Keep the connection open
            while (true) {
                // Clear output buffer to prevent memory issues
                ob_end_flush();

                // Get latest voter count
                $voterCount = Vote::where('election_id', $election->id)
                    ->distinct('user_id')
                    ->count('user_id');

                // Get candidates with their vote counts
                $candidates = Candidate::where('election_id', $election->id)
                    ->get()
                    ->map(function ($candidate) {
                        $voteCount = Vote::where('candidate_id', $candidate->id)->count();
                        return [
                            'candidate_id' => $candidate->id,
                            'name' => $candidate->name,
                            'image_url' => $candidate->image_url,
                            'vote_count' => $voteCount
                        ];
                    });

                // Get election status
                $today = Carbon::now();
                $electionDate = Carbon::parse($election->election_date);

                $status = 'upcoming';
                if ($today->isSameDay($electionDate)) {
                    $status = 'active';
                } elseif ($today->isAfter($electionDate)) {
                    $status = 'closed';
                }

                // Create the data payload
                $data = [
                    'election_id' => $election->id,
                    'title' => $election->title,
                    'election_date' => Carbon::parse($election->election_date)->translatedFormat('l, d F Y'),
                    'status' => $status,
                    'voter_count' => $voterCount,
                    'candidates' => $candidates,
                    'timestamp' => now()->toIso8601String()
                ];

                // Send the SSE data
                echo "event: election_update\n";
                echo "data: " . json_encode($data) . "\n\n";

                // Flush the output buffer
                flush();

                // Sleep for a short time to prevent high CPU usage
                // Adjust this value based on your needs
                sleep(2);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no' // Disable buffering for Nginx
        ]);

        return $response;
    }
}
