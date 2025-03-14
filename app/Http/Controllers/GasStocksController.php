<?php

namespace App\Http\Controllers;

use App\Models\GasStocks;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class GasStocksController extends Controller
{
    // Get all gas stocks
    public function index()
    {
        $gasStocks = GasStocks::all();

        return response()->json([
            'success' => true,
            'message' => 'Gas stock list retrieved successfully',
            'result' => $gasStocks
        ]);
    }

    // Get gas stock by ID
    public function show($id)
    {
        $gasStock = GasStocks::findOrFail($id);

        return response()->json([
            'success' => true,
            'message' => 'Gas stock retrieved successfully',
            'result' => $gasStock
        ]);
    }

    // Create new gas stock
   // Create new gas stock (auto add if exists)
public function store(Request $request)
{
    $validator = Validator::make($request->all(), [
        'branch_id' => 'required|exists:branches,id',
        'gas_type' => 'required|in:3kg,5kg,12kg',
        'stock' => 'required|integer|min:1',
    ]);
    if ($validator->fails()) {
        return response()->json([
            'success' => false,
            'message' => 'Validation errors',
            'result' => $validator->errors()
        ], 422);
    }

    // Cek apakah stok untuk branch_id dan gas_type ini sudah ada
    $existingStock = GasStocks::where('branch_id', $request->branch_id)
        ->where('gas_type', $request->gas_type)
        ->first();

    if ($existingStock) {
        // Jika ada, tambahkan stok
        $existingStock->stock += $request->stock;
        $existingStock->save();

        return response()->json([
            'success' => true,
            'message' => 'Gas stock updated successfully (added to existing)',
            'result' => $existingStock
        ], 200);
    }

    // Jika tidak ada, buat stok baru
    $gasStock = GasStocks::create([
        'branch_id' => $request->branch_id,
        'gas_type' => $request->gas_type,
        'stock' => $request->stock,
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Gas stock added successfully',
        'result' => $gasStock
    ], 201);
}


    public function update(Request $request, $id)
    {
        $gasStock = GasStocks::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'branch_id' => 'sometimes|exists:branches,id',
            'gas_type' => 'sometimes|in:3kg,5kg,12kg', // Hanya menerima nilai dari ENUM
            'stock' => 'sometimes|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $gasStock->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Gas stock updated successfully',
            'result' => $gasStock
        ]);
    }
 

    // Delete gas stock (Soft Delete)
    public function destroy($id)
    {
        $gasStock = GasStocks::findOrFail($id);
        $gasStock->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gas stock deleted successfully',
            'result' => null
        ]);
    }

    // Restore soft-deleted gas stock
    public function restore($id)
    {
        $gasStock = GasStocks::onlyTrashed()->findOrFail($id);
        $gasStock->restore();

        return response()->json([
            'success' => true,
            'message' => 'Gas stock restored successfully',
            'result' => $gasStock
        ]);
    }

    // admin cabang
    public function indexByBranch()
    {
        $user = Auth::user();
        $gasStocks = GasStocks::where('branch_id', $user->branch_id)->get();

        return response()->json([
            'success' => true,
            'message' => 'Gas stock for this branch',
            'result' => $gasStocks
        ]);
    }

    //admin cabang
    public function storeByBranch(Request $request)
    {
        $user = Auth::user();
        
        $validator = Validator::make($request->all(), [
            'gas_type' => 'required|in:elpiji_3kg,elpiji_12kg,bluegas_5kg',
            'stock' => 'required|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $gasStock = GasStocks::create([
            'branch_id' => $user->branch_id,
            'gas_type' => $request->gas_type,
            'stock' => $request->stock,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Gas stock added successfully',
            'result' => $gasStock
        ], 201);
    }

    // 🔹 Update stok gas cabang sendiri
    public function updateByBranch(Request $request, $id)
    {
        $user = Auth::user();
        $gasStock = GasStocks::where('id', $id)->where('branch_id', $user->branch_id)->first();

        if (!$gasStock) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or gas stock not found'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'gas_type' => 'sometimes|in:elpiji_3kg,elpiji_12kg,bluegas_5kg',
            'stock' => 'sometimes|integer|min:1',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'result' => $validator->errors()
            ], 422);
        }

        $gasStock->update($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Gas stock updated successfully',
            'result' => $gasStock
        ]);
    }

    // 🔹 Hapus stok gas di cabang sendiri
    public function destroyByBranch($id)
    {
        $user = Auth::user();
        $gasStock = GasStocks::where('id', $id)->where('branch_id', $user->branch_id)->first();

        if (!$gasStock) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized or gas stock not found'
            ], 403);
        }

        $gasStock->delete();

        return response()->json([
            'success' => true,
            'message' => 'Gas stock deleted successfully'
        ]);
    }

}
