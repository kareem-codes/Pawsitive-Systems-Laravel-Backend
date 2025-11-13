<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Pet;
use App\Http\Requests\StorePetRequest;
use App\Http\Requests\UpdatePetRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PetController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Pet::with(['owner', 'vaccinations']);

        // Filter by owner if user is an owner (customer)
        if ($request->user()->isOwner()) {
            $query->where('user_id', $request->user()->id);
        } else {
            // Staff can filter by owner
            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('species', 'like', "%{$search}%")
                  ->orWhere('breed', 'like', "%{$search}%")
                  ->orWhere('microchip_id', 'like', "%{$search}%");
            });
        }

        // Filter by species
        if ($request->has('species')) {
            $query->where('species', $request->species);
        }

        $pets = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($pets);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePetRequest $request): JsonResponse
    {
        $this->authorize('create', Pet::class);

        $data = $request->validated();
        
        // Auto-assign the authenticated user as the owner if not provided
        if (!isset($data['user_id']) && $request->user()->isOwner()) {
            $data['user_id'] = $request->user()->id;
        }
        if(!empty($request->owner_id)){
            $data['user_id'] = $request->owner_id;
        }
        
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            $photoName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('images/pets'), $photoName);
            $data['photo'] = 'images/pets/' . $photoName;
        }
        
        $pet = Pet::create($data);
        $pet->load('owner');

        return response()->json($pet, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Pet $pet): JsonResponse
    {
        $this->authorize('view', $pet);

        $pet->load(['owner', 'appointments', 'medicalRecords', 'vaccinations']);

        return response()->json($pet);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePetRequest $request, Pet $pet): JsonResponse
    {
        $this->authorize('update', $pet);

        $data = $request->validated();
        
        if ($request->hasFile('photo')) {
            // Delete old photo if exists
            if ($pet->photo && file_exists(public_path($pet->photo))) {
                unlink(public_path($pet->photo));
            }
            
            $photo = $request->file('photo');
            $photoName = time() . '_' . uniqid() . '.' . $photo->getClientOriginalExtension();
            $photo->move(public_path('images/pets'), $photoName);
            $data['photo'] = 'images/pets/' . $photoName;
        }
        
        $pet->update($data);
        $pet->load('owner');

        return response()->json([
            'message' => 'Pet updated successfully',
            'pet' => $pet,
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pet $pet): JsonResponse
    {
        $this->authorize('delete', $pet);

        $pet->delete();

        return response()->json([
            'message' => 'Pet deleted successfully',
        ]);
    }
}
