<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ProductController extends Controller
{
    /**
     * Display a listing of all products.
     * GET /api/products
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $products = $query->latest()->paginate(10);

        return response()->json([
            'success' => true,
            'data'    => $products,
        ]);
    }

    /**
     * Store a newly created product.
     * POST /api/products
     */
    public function store(Request $request)
    {
        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Hanya admin yang bisa menambah produk
        if ($user['email'] !== 'admin@example.com') {
            return response()->json(['success' => false, 'message' => 'Hanya admin yang bisa menambah produk.'], 403);
        }

        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'price'       => 'required|numeric|min:0',
            'description' => 'nullable|string',
            'stock'       => 'required|integer|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product created successfully',
            'data'    => $product,
        ], 201);
    }

    /**
     * Display the specified product.
     * GET /api/products/{id}
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data'    => $product,
        ]);
    }

    /**
     * Update the specified product.
     * PUT /api/products/{id}
     */
    public function update(Request $request, $id)
    {
        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Hanya admin yang bisa mengupdate produk
        if ($user['email'] !== 'admin@example.com') {
            return response()->json(['success' => false, 'message' => 'Hanya admin yang bisa mengupdate produk.'], 403);
        }

        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'price'       => 'sometimes|required|numeric|min:0',
            'description' => 'nullable|string',
            'stock'       => 'sometimes|required|integer|min:0',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Product updated successfully',
            'data'    => $product,
        ]);
    }

    /**
     * Remove the specified product.
     * DELETE /api/products/{id}
     */
    public function destroy(Request $request, $id)
    {
        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated.'], 401);
        }

        // Hanya admin yang bisa menghapus produk
        if ($user['email'] !== 'admin@example.com') {
            return response()->json(['success' => false, 'message' => 'Hanya admin yang bisa menghapus produk.'], 403);
        }

        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully',
        ]);
    }

    /*
    |----------------------------------------------------------------------
    | Internal Endpoints — Dipanggil oleh Order Service
    |----------------------------------------------------------------------
    */

    /**
     * Check stock availability.
     * POST /api/products/{id}/check-stock
     */
    public function checkStock(Request $request, $id)
    {
        $product = Product::find($id);

        if (! $product) {
            return response()->json([
                'success' => false,
                'message' => 'Product not found',
            ], 404);
        }

        $quantity = $request->input('quantity', 1);

        return response()->json([
            'success'   => true,
            'available' => $product->stock >= $quantity,
            'data'      => $product,
        ]);
    }

    /**
     * Reduce stock after order created.
     * POST /api/products/{id}/reduce-stock
     */
    public function reduceStock(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $quantity = $request->input('quantity', 1);

        if ($product->stock < $quantity) {
            return response()->json([
                'success' => false,
                'message' => "Stok tidak cukup. Tersedia: {$product->stock}",
            ], 422);
        }

        $product->decrement('stock', $quantity);

        return response()->json([
            'success' => true,
            'message' => 'Stock reduced successfully',
            'data'    => $product->fresh(),
        ]);
    }

    /**
     * Restore stock after order cancelled.
     * POST /api/products/{id}/restore-stock
     */
    public function restoreStock(Request $request, $id)
    {
        $product = Product::findOrFail($id);
        $quantity = $request->input('quantity', 1);

        $product->increment('stock', $quantity);

        return response()->json([
            'success' => true,
            'message' => 'Stock restored successfully',
            'data'    => $product->fresh(),
        ]);
    }

    /**
     * Verify user token via User Service (port 8001).
     */
    private function verifyUser(?string $token): ?array
    {
        if (! $token) {
            return null;
        }

        $userServiceUrl = env('USER_SERVICE_URL', 'http://127.0.0.1:8001');

        try {
            $response = Http::withToken($token)
                ->get("{$userServiceUrl}/api/user/verify");

            if ($response->successful() && $response->json('success')) {
                return $response->json('data');
            }
        } catch (\Exception $e) {
            // User Service not available
        }

        return null;
    }
}
