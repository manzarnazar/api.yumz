<?php

namespace App\Http\Controllers\API\v1\Rest;

use App\Helpers\ResponseError;
use App\Helpers\Utility;
use App\Http\Requests\DeliveryZone\CheckDistanceRequest;
use App\Http\Requests\DeliveryZone\DistanceRequest;
use App\Http\Requests\FilterParamsRequest;
use App\Models\DeliveryZone;
use App\Models\Shop;
use App\Models\ShopDeliveryZipcode;
use App\Traits\SetCurrency;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DeliveryZoneController extends RestBaseController
{
	use SetCurrency;

	/**
	 * @param int $shopId
	 * @return array
	 */
	public function getByShopId(int $shopId): array
	{
		try {
			$deliveryZone = DeliveryZone::where('shop_id', $shopId)->firstOrFail();

			return [
				'status' => true,
				'code'   => ResponseError::NO_ERROR,
				'data'   => $deliveryZone,
			];
		} catch (Throwable $e) {
			$this->error($e);
			return [
				'status' => false,
				'code'   => ResponseError::ERROR_404,
			];
		}
	}

	/**
	 * @param int $deliveryId
	 * @param Request $request
	 * @return float|JsonResponse
	 */
	public function deliveryCalculatePrice(int $deliveryId, Request $request): float|JsonResponse
	{
		/** @var DeliveryZone $deliveryZone */
		$deliveryZone = DeliveryZone::find($deliveryId);

		if (!$deliveryZone) {
			return $this->onErrorResponse([
				'code'    => ResponseError::ERROR_404,
				'message' => __('errors.' . ResponseError::ERROR_404, locale: $this->language)
			]);
		}

		$km = $request->input('km');

		if ($km <= 0) {
			$km = 1;
		}

		return round(
			($deliveryZone->shop->price + ($deliveryZone->shop->price_per_km * $km)) * $this->currency(), 2
		);
	}

	/**
	 * @param DistanceRequest $request
	 * @return array
	 */
	public function distance(DistanceRequest $request): array
	{
		return [
			'status' => true,
			'code'   => ResponseError::NO_ERROR,
			'data'   => (new Utility)->getDistance($request->input('origin'), $request->input('destination')),
		];
	}

	/**
	 * @param CheckDistanceRequest $request
	 * @return JsonResponse
	 */
	public function checkDistance(CheckDistanceRequest $request): JsonResponse
	{
		$requestedZipCode = $request->input('address.zip_code');
		$requestedCity = $request->input('address.city');

		// Check if the requested zip code exists in the shop_delivery_zipcodes table
		$locationExists = ShopDeliveryZipcode::where('zip_code', $requestedZipCode)
        ->orWhere('city', $requestedCity)
        ->exists();
	
		// If the zip code exists, return success response
		if ($locationExists) {
			return $this->successResponse('success', 'Zip code matched successfully');
		}
	
		// If the zip code does not exist, return error response
		return $this->onErrorResponse([
			'code'    => ResponseError::ERROR_400,
			'message' => __('errors.' . ResponseError::ERROR_400, locale: $this->language)
		]);
	}

	/**
	 * @param int $id
	 * @param CheckDistanceRequest $request
	 * @return JsonResponse
	 */
	public function checkDistanceByShop(int $id, CheckDistanceRequest $request): JsonResponse
	{


		$requestedZipCode = $request->input('address.zip_code');
    	$requestedCity = $request->input('address.city');

    // Check if the requested zip code or city exists in the shop_delivery_zipcodes table for the given shop ID
    $locationExists = ShopDeliveryZipcode::where('shop_id', $id)
        ->where(function ($query) use ($requestedZipCode, $requestedCity) {
            $query->where('zip_code', $requestedZipCode)
                  ->orWhere('city', $requestedCity);
        })
        ->exists();
	
		// If the zip code exists, return success response
		if ($locationExists) {
			return $this->successResponse('success', 'Zip code matched successfully');
		}
	
		// If the zip code does not exist, return error response
		return $this->onErrorResponse([
			'code'    => ResponseError::ERROR_400,
			'message' => __('errors.' . ResponseError::ERROR_400, locale: $this->language)
		]);
		// /** @var Shop $shop */
		// $shop = Shop::with('deliveryZone:id,shop_id,address')->whereHas('deliveryZone')
		// 	->where([
		// 		['open', 1],
		// 		['status', 'approved'],
		// 	])
		// 	->find($id);

		// if (empty($shop?->deliveryZone)) {
		// 	return $this->onErrorResponse([
		// 		'code'    => ResponseError::ERROR_404,
		// 		'message' => __('errors.' . ResponseError::SHOP_OR_DELIVERY_ZONE, locale: $this->language)
		// 	]);
		// }

		// $check = Utility::pointInPolygon($request->input('address'), $shop?->deliveryZone->address);

		// if ($check) {
		// 	return $this->successResponse('success');
		// }

		// return $this->onErrorResponse([
		// 	'code'    => ResponseError::ERROR_400,
		// 	'message' => __('errors.' . ResponseError::NOT_IN_POLYGON, locale: $this->language)
		// ]);
	}

}

