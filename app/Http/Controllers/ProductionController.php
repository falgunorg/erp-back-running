<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Production;

class ProductionController extends Controller {

    public function index(Request $request) {
        $date = $request->date ?? date('Y-m-d');

        return Production::where('production_date', $date)
                        ->where('company_id', $request->user->company)
                        ->orderBy('unit')
                        ->orderBy('line_no')
                        ->get();
    }

    public function store(Request $request) {
        // Get logged-in user data (property, not method)
        $company_id = $request->user->company;
        $user_id = $request->user->id;

        // 1. Validation (NO company_id / user_id from request)
        $validated = $request->validate([
            'production_date' => 'required|date',
            'unit' => 'required|string',
            'line_no' => 'required|integer',
            'buyer' => 'nullable|string',
            'item' => 'nullable|string',
            'style' => 'nullable|string',
            'cm_val' => 'nullable|numeric',
            'fob_val' => 'nullable|numeric',
            'smv' => 'nullable|numeric',
            'mp' => 'nullable|integer',
            'run_day' => 'nullable|integer',
            'wh' => 'nullable|integer',
            'target' => 'nullable|integer',
            'last_day_achieve' => 'nullable|integer',
            'remarks' => 'nullable|string',
        ]);

        // 2. Unique check (date + company + unit + line)
        $exists = Production::where([
                    'production_date' => $validated['production_date'],
                    'company_id' => $company_id,
                    'unit' => $validated['unit'],
                    'line_no' => $validated['line_no'],
                ])->exists();

        if ($exists) {
            return response()->json([
                        'message' => "Entry already exists for Line {$validated['line_no']} ({$validated['unit']}) on this date."
                            ], 422);
        }

        // 3. Inject company_id & user_id (server-controlled)
        $validated['company_id'] = $company_id;
        $validated['user_id'] = $user_id;

        // 4. Create record
        $production = Production::create($validated);

        return response()->json([
                    'message' => 'Record created successfully',
                    'data' => $production
                        ], 201);
    }

    /**
     * Update hourly production or line details
     */
    public function update(Request $request, $id) {
        $production = Production::where('id', $id)
                ->where('company_id', $request->user->company)
                ->firstOrFail();

        // Validation rules
        $rules = [
            'buyer' => 'sometimes|string|nullable',
            'style' => 'sometimes|string|nullable',
            'target' => 'sometimes|integer|nullable',
            'remarks' => 'sometimes|string|nullable',
        ];

        // Hourly fields h1–h18
        for ($i = 1; $i <= 18; $i++) {
            $rules["h$i"] = 'sometimes|integer|nullable';
        }

        $validated = $request->validate($rules);

        // Update only validated data
        $production->update($validated);

        return response()->json([
                    'message' => 'Data updated successfully',
                    'data' => $production
        ]);
    }

    public function destroy(Request $request, $id) {
        $production = Production::where('id', $id)
                ->where('company_id', $request->user->company)
                ->firstOrFail();

        $production->delete();

        return response()->json([
                    'message' => 'Record deleted successfully'
        ]);
    }
}
