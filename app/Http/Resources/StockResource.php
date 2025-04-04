<?php

namespace App\Http\Resources;

use App\Helpers\Admin\Utility;
use App\Http\Resources\Bonus\SimpleBonusResource;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request): array
    {
        /** @var Stock|JsonResource $this */

		$inventoryItems = $this->relationLoaded('inventoryItems');

        return [
            'id'            => $this->id,
            'countable_id'  => $this->when($this->countable_id, $this->countable_id),
            'price'         => $this->rate_price,
            'quantity'      => $this->quantity,
            'sku'           => $this->when($this->sku, $this->sku),
            'min_quantity'  => $this->when($this->getOriginal('pivot_min_quantity'), $this->getOriginal('pivot_min_quantity')),
            'discount'      => $this->when($this->rate_actual_discount, $this->rate_actual_discount),
            'tax'           => $this->rate_tax_price,
            'total_price'   => $this->rate_total_price,
            'addon'         => (boolean)$this->addon,
            'cost_price'    => $inventoryItems ? Utility::calculateCostPrice($this) : 0,
            'addons'        => StockAddonResource::collection($this->whenLoaded('addons')),
            'deleted_at'    => $this->when($this->deleted_at, $this->deleted_at?->format('Y-m-d H:i:s') . 'Z'),

            // Relation
            'extras'        => ExtraValueResource::collection($this->whenLoaded('stockExtras')),
            'product'       => ProductResource::make($this->whenLoaded('countable')),
            'bonus'         => SimpleBonusResource::make($this->whenLoaded('bonus')),
            'logs'          => ModelLogResource::collection($this->whenLoaded('logs')),
        ];
    }
}
