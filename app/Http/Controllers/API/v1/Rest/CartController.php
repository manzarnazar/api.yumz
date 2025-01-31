<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Http\Requests\Cart\GroupStoreRequest;
use App\Http\Requests\Cart\IndexRequest;
use App\Http\Requests\Cart\OpenCartRequest;
use App\Http\Requests\Cart\RestInsertProductsRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Http\Resources\Cart\CartResource;
use App\Models\TestCart;
use App\Models\TestCartDetail;
use App\Repositories\CartRepository\CartRepository;
use App\Services\CartService\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends RestBaseController
{
    private CartRepository $cartRepository;
    private CartService $cartService;

    public function __construct(CartRepository $cartRepository, CartService $cartService)
    {
        parent::__construct();
        $this->cartRepository = $cartRepository;
        $this->cartService = $cartService;
    }

    public function get(int $id, IndexRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $cart = $this->cartRepository->get($validated, $id);

        if (empty($cart)) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_404,
                'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
            ]);
        }

        if (!$cart->userCarts?->where('uuid', $request->input('user_cart_uuid'))?->first()) {
            return $this->onErrorResponse([
                'code'    => ResponseError::ERROR_400,
                'message' => __('errors.' . ResponseError::ERROR_400, locale: $this->language)
            ]);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            CartResource::make($cart)
        );
    }

    public function openCart(OpenCartRequest $request): JsonResponse
    {
        $collection = $request->validated();

        $result = $this->cartService->openCart($collection);

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            data_get($result, 'data')
        );
    }

    public function addProductstore(GroupStoreRequest $request): JsonResponse
    {
        $result = $this->cartService->groupCreate($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            data_get($result, 'data')
        );
    }

    public function insertProducts(RestInsertProductsRequest $request): JsonResponse
    {
        if (empty($request->input('user_cart_uuid'))) {
            return $this->onErrorResponse([
                'code' => ResponseError::ERROR_400,
                'message' => 'cart id is invalid'
            ]);
        }

        $result = $this->cartService->groupInsertProducts($request->validated());

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_CREATED, locale: $this->language),
            data_get($result, 'data')
        );
    }
    
    public function addProduct(Request $request)
    {
        \DB::beginTransaction();
        try {
            // Validate the incoming request
            $validated = $request->validate([
                'shop_id' => 'required|exists:shops,id',
                'guest_id' => 'required|exists:guests,id',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
            ]);

            // Create the cart if not exists
            $cart = TestCart::firstOrCreate([
                'shop_id' => $validated['shop_id'],
                'guest_id' => $validated['guest_id'],
                'status' => 1,
            ]);

            foreach ($validated['products'] as $product) {
                $productId = $product['id'];
                $quantity = $product['quantity'];

                // Insert into cart details
                TestCartDetail::create([
                    'cart_id' => $cart->id,
                    'stock_id' => $product['stock']['id'],
                    'quantity' => $quantity,
                    'price' => $product['stock']['price'],
                    'discount' => $product['stock']['total_price'] - $product['stock']['price'],
                    'bonus' => $product['stock']['bonus'] ?? 0,
                    'bonus_type' => $product['stock']['bonus'] ? 'percent' : null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            \DB::commit();
            return response()->json(['message' => 'Products added to cart successfully.'], 201);

        } catch (\Exception $e) {
            \DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Delete product from the cart
    public function deleteProduct(Request $request)
    {
        // Validate the incoming request
        $validated = $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'product_id' => 'required|exists:cart_details,stock_id',
        ]);

        try {
            // Delete the product from the cart
            CartDetail::where('cart_id', $validated['cart_id'])
                      ->where('stock_id', $validated['product_id'])
                      ->delete();

            return response()->json(['message' => 'Product removed from cart successfully.'], 200);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function userCartDelete(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->cartService->userCartDelete(
            $request->input('ids', []),
            $request->input('cart_id', 0)
        );

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    public function cartProductDelete(FilterParamsRequest $request): JsonResponse
    {
        $result = $this->cartService->cartProductDelete($request->input('ids', []));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        return $this->successResponse(
            __('errors.' . ResponseError::RECORD_WAS_SUCCESSFULLY_DELETED, locale: $this->language)
        );
    }

    public function statusChange(string $userCartUuid, Request $request): JsonResponse
    {
        $result = $this->cartService->statusChange($userCartUuid, $request->input('cart_id', 0));

        if (!data_get($result, 'status')) {
            return $this->onErrorResponse($result);
        }

        $data = data_get($result, 'data');

        return $this->successResponse(
            __('errors.' . ResponseError::SUCCESS, locale: $this->language),
            $data
        );
    }

}
