<?php

namespace App\Repositories\CartRepository;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Resources\Cart\CartDetailResource;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Language;
use App\Models\Order;
use App\Models\Settings;
use App\Repositories\CoreRepository;
use App\Services\CartService\CartService;
use App\Traits\SetCurrency;
use DB;

class CartRepository extends CoreRepository
{
    use SetCurrency;

    protected function getModelClass(): string
    {
        return Cart::class;
    }

	/**
	 * @param array $data
	 * @param int|null $cartId
	 * @return Cart|null
	 */
    public function get(array $data, ?int $cartId = null): ?Cart
    {
        $userId  = auth('sanctum')->id();
        $locale  = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');

        $cart = $this->model()
            ->with([
                'shop:id',
                'shop.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
                'userCarts.cartDetails.stock.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
            ])
            ->when($cartId, fn($q) => $q->where('id', $cartId))
            ->when($userId && !data_get($data, 'user_cart_uuid'), fn($q) => $q->where('owner_id', $userId))
            ->when(data_get($data, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->first();

        if(empty($cart)) {
            return $cart;
        }

        /** @var Cart $cart */
        (new CartService)->calculateTotalPrice($cart);

        $cart = $this->model()->with([
            'shop.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
            'userCarts.cartDetails' => fn($q) => $q->whereNull('parent_id'),
            'userCarts.cartDetails.stock.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
            'userCarts.cartDetails.stock.countable.unit.translation' => fn($q) => $q
                ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
            'userCarts.cartDetails.stock.countable.translation' => fn($q) => $q
                ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
            'userCarts.cartDetails.stock.stockExtras.group.translation' => fn($q) => $q
                ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),

            'userCarts.cartDetails.children.stock.countable.unit.translation' => fn($q) => $q
                ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
            'userCarts.cartDetails.children.stock.countable.translation' => fn($q) => $q
                ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
            'userCarts.cartDetails.children.stock.stockExtras.group.translation' => fn($q) => $q
                ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
        ])
            ->when($cartId, fn($q) => $q->where('id', $cartId))
			->when($userId && !data_get($data, 'user_cart_uuid'), fn($q) => $q->where('owner_id', $userId))
			->when(data_get($data, 'shop_id'), fn($q, $shopId) => $q->where('shop_id', $shopId))
            ->first();

        $currency = Currency::currenciesList()->where('id', (int)request('currency_id'))->first();

        if (!empty($cart) && !empty($currency?->id) && $cart->currency_id !== (int)$currency?->id) {
            $cart->update(['currency_id' => $currency->id, 'rate' => $currency->rate]);
        }

        return $cart;
    }

    /**
     * @param int $id
     * @param array $data
     *
     * @return array
     */
    public function calculateByCartId(int $id, array $data): array
{
    $locale   = data_get(Language::languagesList()->where('default', 1)->first(), 'locale');
    $currency = Currency::currenciesList()->where('id', data_get($data, 'currency_id'))->first();

    /** @var Cart $cart */
    $cart = Cart::with([
        'shop:id,location,tax,price,price_per_km,uuid,logo_img,status',
        'shop.translation' => fn($q) => $q->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
        'shop.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
        'userCarts.cartDetails.stock.countable.unit.translation' => fn($q) => $q
            ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
        'userCarts.cartDetails.stock.countable.translation' => fn($q) => $q
           ->where(fn($q) => $q->where('locale', $this->language)->orWhere('locale', $locale)),
        'userCarts.cartDetails.stock.bonus' => fn($q) => $q->where('expired_at', '>', now())->where('status', true),
        'userCarts.cartDetails.stock.countable.discounts' => fn($q) => $q->where('start', '<=', today())
            ->where('end', '>=', today())
            ->where('active', 1),
    ])->withCount('userCarts')->find($id);

    if (!$cart || !$cart->shop?->id || $cart->user_carts_count === 0) {
        return ['status' => false, 'code' => ResponseError::ERROR_404];
    }

    if ($currency) {
        $cart->update(['currency_id' => $currency->id, 'rate' => $currency->rate]);
    }

    $checkPhone = $this->checkPhoneIfRequired($data);
    if (!data_get($checkPhone, 'status')) {
        return $checkPhone;
    }

    $totalTax = $price = $discount = $deliveryFee = 0;
    $cartDetails = $cart->userCarts->flatMap(fn($userCart) => $userCart->cartDetails);

    foreach ($cartDetails as $cartDetail) {
        if ($cartDetail->quantity === 0 || !$cartDetail->stock) {
            $cartDetail->children()->delete();
            $cartDetail->delete();
            continue;
        }

        $totalTax += $cartDetail->stock->rate_tax_price;
        $price += $cartDetail->rate_price;
        $discount += $cartDetail->rate_discount;

        foreach ($cartDetail->children as $child) {
            $totalTax += $child->stock->rate_tax_price;
            $price += $child->rate_price;
            $discount += $child->rate_discount;
        }
    }

    $rate = $currency?->rate ?? $cart->rate;

    // Delivery Fee Calculation
    if ($zipcode = data_get($data, 'zipcode')) {
        $deliveryFee = DB::table('shop_delivery_zipcodes')
            ->where('zip_code', $zipcode)
            ->where('shop_id', $cart->shop->id)
            ->value('delivery_price') ?? 0;
    } elseif (data_get($data, 'type') === Order::DELIVERY) {
        $km = (new Utility)->getDistance($cart->shop->location, data_get($data, 'address'));
        $deliveryFee = (new Utility)->getPriceByDistance($km, $cart->shop, (float)data_get($data, 'rate', 1));
    }

    // Coupon Application
    $coupon = Coupon::checkCoupon(data_get($data, 'coupon'), $cart->shop_id)->first();
    $couponPrice = 0;

    if ($coupon) {
        $couponPrice = $this->checkCoupon($coupon, $coupon->for === 'delivery_fee' ? $deliveryFee : $cart->total_price);
        if ($coupon->for === 'delivery_fee') {
            $deliveryFee -= $couponPrice;
        } else {
            $price -= $couponPrice;
        }
    }

    $tips = (float)data_get($data, 'tips', 0);

    // Final Price Calculation
    $totalPrice = max($price + $deliveryFee + $totalTax + $tips, 0);

    return [
        'status' => true,
        'code'   => ResponseError::NO_ERROR,
        'data'   => [
            'products' => CartDetailResource::collection($cartDetails),
            'total_tax' => $totalTax,
            'price' => $price,
            'total_price' => $totalPrice,
            'total_discount' => $discount,
            'delivery_fee' => $deliveryFee,
            'service_fee' => $rate * ((double)Settings::where('key', 'service_fee')->first()?->value ?: 0),
            'tips' => $tips,
            'rate' => $rate,
            'coupon_price' => $couponPrice,
        ],
    ];
}

	private function checkPhoneIfRequired(array $data): array
	{
		$existPhone = DB::table('users')
			->whereNotNull('phone')
			->where('id', data_get($data, 'user_id'))
			->exists();

		$beforeOrderPhoneRequired = Settings::where('key', 'before_order_phone_required')->first();

		if (
			data_get($data, 'delivery_type') == Order::DELIVERY
			&& $beforeOrderPhoneRequired?->value && (!$existPhone && !data_get($data, 'phone'))
		) {
			return [
				'status'  => false,
				'message' => __('errors.' . ResponseError::ERROR_117, locale: $this->language),
				'code'    => ResponseError::ERROR_117
			];
		}

		return ['status' => true];
	}

    /**
     * @param Coupon $coupon
     * @param $totalPrice
     * @return float|int|null
     */
    public function checkCoupon(Coupon $coupon, $totalPrice): float|int|null
    {
        if ($coupon->qty <= 0) {
            return 0;
        }

        $price = $coupon->type === 'percent' ? ($totalPrice / 100) * $coupon->price : $coupon->price;

		return $price > 0 ? $price * $this->currency() : 0;
	}

}
