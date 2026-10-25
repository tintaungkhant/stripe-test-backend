<?php

namespace App\Services;

use App\Models\CartItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class CartService
{
    public const MAX_QUANTITY = 99;

    /**
     * Cart items whose product is still for sale, oldest first.
     *
     * @return Collection<int, CartItem>
     */
    public function items(User $user): Collection
    {
        return $user->cartItems()->whereHas('product')->with('product')->oldest('id')->get();
    }

    /**
     * Prices come from the products, so the total follows any price change.
     *
     * @param  Collection<int, CartItem>  $items
     */
    public function total(Collection $items): string
    {
        $total = $items->sum(fn (CartItem $item) => $item->product->price * $item->quantity);

        return number_format($total, 2, '.', '');
    }

    /**
     * Add the product, or raise its quantity when it is already in the cart.
     */
    public function add(User $user, Product $product, int $quantity = 1): CartItem
    {
        $item = $user->cartItems()->firstOrNew(['product_id' => $product->id]);

        $item->quantity = min(($item->quantity ?? 0) + $quantity, self::MAX_QUANTITY);
        $item->save();

        return $item->setRelation('product', $product);
    }

    public function updateQuantity(User $user, Product $product, int $quantity): CartItem
    {
        $item = $user->cartItems()->where('product_id', $product->id)->firstOrFail();

        $item->update(['quantity' => $quantity]);

        return $item->setRelation('product', $product);
    }

    public function remove(User $user, Product $product): void
    {
        $user->cartItems()->where('product_id', $product->id)->delete();
    }

    /**
     * Take what the order paid for out of the customer's cart.
     */
    public function removePurchased(Order $order): void
    {
        $order->user->cartItems()
            ->whereIn('product_id', $order->items()->select('product_id'))
            ->delete();
    }
}
