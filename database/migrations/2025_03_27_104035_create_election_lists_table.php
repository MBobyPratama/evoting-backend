<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('election_lists', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('election_date');
            $table->enum('status', ['active', 'closed', 'upcoming']);
            $table->integer('candidate_count')->default(0);
            $table->integer('voter_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('election_lists');
    }
};
