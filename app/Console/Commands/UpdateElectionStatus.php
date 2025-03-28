<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ElectionList;
use Carbon\Carbon;

class UpdateElectionStatus extends Command
{
    protected $signature = 'elections:update-status';
    protected $description = 'Update status of all elections based on their dates';

    public function handle()
    {
        $elections = ElectionList::all();
        $updated = 0;

        foreach ($elections as $election) {
            $oldStatus = $election->status;

            $today = Carbon::today();
            $electionDate = Carbon::parse($election->election_date);

            if ($today->isSameDay($electionDate)) {
                $election->status = 'active';
            } elseif ($today->isAfter($electionDate)) {
                $election->status = 'closed';
            } else {
                $election->status = 'upcoming';
            }

            // Only save if status changed
            if ($oldStatus !== $election->status) {
                $election->save();
                $updated++;
            }
        }

        $this->info("Updated status for {$updated} elections");
        return Command::SUCCESS;
    }
}
