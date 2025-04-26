<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Vote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CandidateController extends Controller
{
    /**
     * Display a listing of the candidates.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $candidates = Candidate::all();

        return response()->json(['data' => $candidates]);
    }

    /**
     * Store a newly created candidate in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'election_id' => 'required|exists:election_lists,id',
            'number' => 'required|string|regex:/^[0-9]+$/',
            'name' => [
                'required',
                'string',
                'regex:/^[a-zA-Z\s&]+$/'
            ],
            'vision' => 'required|string',
            'mission' => 'required|string',
            'image_url' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if (Candidate::where('election_id', $validated['election_id'])
            ->where('number', $validated['number'])
            ->exists()
        ) {
            return response()->json([
                'message' => 'Candidate number already exists for this election'
            ], 400);
        }

        try {
            if ($request->hasFile('image_url')) {
                $image = $request->file('image_url');
                $imageName = time() . '_' . $image->getClientOriginalName();

                $path = $image->storeAs('candidates', $imageName, 'public');

                $validated['image_url'] = '/storage/' . $path;
            }

            $candidate = Candidate::create($validated);

            return response()->json([
                'message' => 'Candidate created successfully',
                'data' => $candidate
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create candidate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified candidate.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $candidate = Candidate::find($id);

        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate not found'
            ], 404);
        }

        return response()->json(['data' => $candidate]);
    }

    /**
     * Update the specified candidate in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        $candidate = Candidate::find($id);

        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate not found'
            ], 404);
        }

        $validated = $request->validate([
            'number' => 'sometimes|required|string|regex:/^[0-9]+$/',
            'name' => [
                'sometimes',
                'required',
                'string',
                'regex:/^[a-zA-Z\s&]+$/'
            ],
            'vision' => 'sometimes|required|string',
            'mission' => 'sometimes|required|string',
            'image_url' => 'sometimes|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if (isset($validated['number']) && $candidate->number !== $validated['number']) {
            if (Candidate::where('election_id', $candidate->election_id)
                ->where('number', $validated['number'])
                ->exists()
            ) {
                return response()->json([
                    'message' => 'Candidate number already exists for this election'
                ], 400);
            }
        }

        try {
            if ($request->hasFile('image_url')) {
                $oldImagePath = str_replace('/storage/', '', $candidate->image_url);
                if ($oldImagePath && Storage::disk('public')->exists($oldImagePath)) {
                    Storage::disk('public')->delete($oldImagePath);
                }

                $image = $request->file('image_url');
                $imageName = time() . '_' . $image->getClientOriginalName();

                $path = $image->storeAs('candidates', $imageName, 'public');

                $validated['image_url'] = '/storage/' . $path;
            }

            $candidate->update($validated);

            return response()->json([
                'message' => 'Candidate updated successfully',
                'data' => $candidate
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update candidate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified candidate in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $candidate = Candidate::find($id);

        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate not found'
            ], 404);
        }

        // Begin database transaction
        \Illuminate\Support\Facades\DB::beginTransaction();

        try {
            // Delete related votes first
            \App\Models\Vote::where('candidate_id', $candidate->id)->delete();

            $imagePath = str_replace('/storage/', '', $candidate->image_url);
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            $candidate->delete();

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'message' => 'Candidate and related votes deleted successfully'
            ]);
        } catch (\Exception $e) {
            // Rollback in case of error
            \Illuminate\Support\Facades\DB::rollBack();

            return response()->json([
                'message' => 'Failed to delete candidate',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display a listing of candidates for mahasiswa.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function indexForMahasiswa()
    {
        $candidates = Candidate::all();

        return response()->json(['data' => $candidates]);
    }

    /**
     * Display the specified candidate for mahasiswa.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function showForMahasiswa($id)
    {
        $candidate = Candidate::find($id);

        if (!$candidate) {
            return response()->json([
                'message' => 'Candidate not found'
            ], 404);
        }

        return response()->json(['data' => $candidate]);
    }
}
