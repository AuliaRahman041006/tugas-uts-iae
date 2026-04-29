<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    /**
     * Display a listing of orders for the authenticated user.
     * GET /api/orders
     */
    public function index(Request $request)
    {
        // Step 2: Sistem mengecek user — panggil User Service
        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Token tidak valid.',
            ], 401);
        }

        if ($user['email'] === 'admin@example.com') {
            // Admin bisa melihat semua order
            $orders = Order::latest()->paginate(10);
        } else {
            // User biasa hanya melihat order miliknya
            $orders = Order::where('user_id', $user['id'])
                ->latest()
                ->paginate(10);
        }

        return response()->json([
            'success' => true,
            'data'    => $orders,
        ]);
    }

    /**
     * Store a newly created order.
     * POST /api/orders
     *
     * ALUR SISTEM:
     * 1. User melakukan order (request masuk)
     * 2. Sistem mengecek user → panggil User Service (port 8001)
     * 3. Sistem mengecek produk & stok → panggil Product Service (port 8002)
     * 4. Order dibuat → simpan di database Order Service
     * 5. Stok produk berkurang → panggil Product Service reduce-stock
     *
     * Expected body:
     * {
     *   "product_id": 1,
     *   "quantity": 2
     * }
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity'   => 'required|integer|min:1',
        ]);

        // ── Step 2: Sistem mengecek user ──────────────────────────────
        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Token tidak valid.',
            ], 401);
        }

        $productId = $request->product_id;
        $quantity  = $request->quantity;

        // ── Step 3: Sistem mengecek produk & stok ─────────────────────
        $productServiceUrl = env('PRODUCT_SERVICE_URL', 'http://127.0.0.1:8002');
        $checkResponse = Http::post("{$productServiceUrl}/api/products/{$productId}/check-stock", [
            'quantity' => $quantity,
        ]);

        if ($checkResponse->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.',
            ], 404);
        }

        $checkData = $checkResponse->json();

        if (! $checkData['available']) {
            return response()->json([
                'success' => false,
                'message' => "Stok tidak cukup. Tersedia: {$checkData['data']['stock']}",
            ], 422);
        }

        $product = $checkData['data'];

        // ── Step 4: Order dibuat ──────────────────────────────────────
        $totalPrice = $product['price'] * $quantity;

        $order = Order::create([
            'user_id'       => $user['id'],
            'product_id'    => $product['id'],
            'product_name'  => $product['name'],
            'product_price' => $product['price'],
            'quantity'      => $quantity,
            'total_price'   => $totalPrice,
            'status'        => 'pending',
        ]);

        // ── Step 5: Stok produk berkurang ─────────────────────────────
        Http::post("{$productServiceUrl}/api/products/{$productId}/reduce-stock", [
            'quantity' => $quantity,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibuat',
            'data'    => $order,
        ], 201);
    }

    /**
     * Display the specified order.
     * GET /api/orders/{id}
     */
    public function show(Request $request, $id)
    {
        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($user['email'] === 'admin@example.com') {
            $order = Order::findOrFail($id);
        } else {
            $order = Order::where('user_id', $user['id'])->findOrFail($id);
        }

        return response()->json([
            'success' => true,
            'data'    => $order,
        ]);
    }

    /**
     * Update order status.
     * PUT /api/orders/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled',
        ]);

        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        if ($user['email'] !== 'admin@example.com') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya admin yang bisa mengubah status order.',
            ], 403);
        }

        $order = Order::findOrFail($id);

        // Jika order sudah cancelled, tidak bisa diubah lagi
        if ($order->status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Order yang sudah dibatalkan tidak bisa diubah statusnya.',
            ], 422);
        }

        // If cancelling, restore stock via Product Service
        if ($request->status === 'cancelled') {
            $productServiceUrl = env('PRODUCT_SERVICE_URL', 'http://127.0.0.1:8002');
            Http::post("{$productServiceUrl}/api/products/{$order->product_id}/restore-stock", [
                'quantity' => $order->quantity,
            ]);
        }

        $order->update(['status' => $request->status]);

        return response()->json([
            'success' => true,
            'message' => 'Status order berhasil diupdate',
            'data'    => $order,
        ]);
    }

    /**
     * Cancel an order (only if pending).
     * POST /api/orders/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        $user = $this->verifyUser($request->bearerToken());
        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $order = Order::where('user_id', $user['id'])->findOrFail($id);

        if ($order->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya order dengan status pending yang bisa dibatalkan.',
            ], 422);
        }

        // Restore stock via Product Service
        $productServiceUrl = env('PRODUCT_SERVICE_URL', 'http://127.0.0.1:8002');
        Http::post("{$productServiceUrl}/api/products/{$order->product_id}/restore-stock", [
            'quantity' => $order->quantity,
        ]);

        $order->update(['status' => 'cancelled']);

        return response()->json([
            'success' => true,
            'message' => 'Order berhasil dibatalkan, stok dikembalikan',
            'data'    => $order,
        ]);
    }

    /**
     * Verify user token via User Service (port 8001).
     * Returns user data or null if invalid.
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
