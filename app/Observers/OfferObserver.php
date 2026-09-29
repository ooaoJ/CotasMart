<?php

namespace App\Observers;

use App\Models\Offer;

class OfferObserver
{
    public function created(Offer $offer): void
    {
        $this->recordSnapshot($offer);
    }

    public function updated(Offer $offer): void
    {
        if ($offer->wasChanged(['current_price', 'shipping_price', 'availability'])) {
            $this->recordSnapshot($offer);
        }
    }

    private function recordSnapshot(Offer $offer): void
    {
        $shipping = (float) ($offer->shipping_price ?? 0);

        $offer->priceSnapshots()->create([
            'price' => $offer->current_price,
            'shipping_price' => $shipping,
            'total_price' => (float) $offer->current_price + $shipping,
            'availability' => $offer->availability,
            'collected_at' => $offer->last_checked_at ?? now(),
        ]);
    }
}

