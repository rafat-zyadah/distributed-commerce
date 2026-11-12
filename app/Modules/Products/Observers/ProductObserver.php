<?php

namespace App\Modules\Products\Observers;

use App\Modules\Products\Models\Product;
use App\Modules\Products\Services\ProductCatalogCache;

/**
 * Every product write (controller, tinker, a future command) passes through here,
 * so the catalog cache is invalidated in one place.
 *
 * Invalidation runs after the save. A single save() is already committed by then,
 * because Eloquent does not wrap it in a transaction. If products are ever written
 * inside a transaction, call invalidate() after the commit. If invalidation ran before
 * the commit, a read in between could cache old data under the new version.
 */
class ProductObserver
{
    public function __construct(private readonly ProductCatalogCache $catalog) {}

    public function saved(Product $product): void
    {
        $this->catalog->invalidate();
    }

    public function deleted(Product $product): void
    {
        $this->catalog->invalidate();
    }
}