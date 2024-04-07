<?php

namespace App\Http\Controllers\API;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Location;
use App\Models\ShippingCharge;
use App\Models\CustomerAddress;
use App\Models\DiscountCoupon;
use Carbon\Carbon;

class CartController extends Controller
{
    public function addToCart(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:products,id',
        ]);

        $product = Product::find($request->id);

        Cart::add($product->id, $product->title, 1, $product->price, ['productImage' => $product->image]);
        Cart::store('user');

        return response()->json([
            'status' => true,
            'message' => $product->title . ' added to cart successfully.'
        ]);
    }

    public function cart()
    {
        $cartContent = Cart::content();
        return response()->json([
            'status' => true,
            'cartContent' => $cartContent
        ]);
    }

    public function updateCart(Request $request)
    {
        $request->validate([
            'rowId' => 'required',
            'qty' => 'required|integer|min:1'
        ]);

        Cart::update($request->rowId, $request->qty);

        return response()->json([
            'status' => true,
            'message' => 'Cart updated successfully.'
        ]);
    }

    public function deleteItem(Request $request)
    {
        $request->validate([
            'rowId' => 'required'
        ]);

        Cart::remove($request->rowId);

        return response()->json([
            'status' => true,
            'message' => 'Item removed from cart successfully.'
        ]);
    }

    public function checkout(Request $request)
    {
        // Apply validation

        $validator = Validator::make($request->all(), [
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'location' => 'required',
            'address' => 'required',
            'city' => 'required',
            'province' => 'required',
            'zip' => 'required',
            'mobile' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Please fix the errors.',
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        // Save user address

        $user = Auth::user();

        CustomerAddress::updateOrCreate(
            ['user_id' => $user->id],
            [
                'user_id' => $user->id,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'location_id' => $request->location,
                'address' => $request->address,
                'apartment' => $request->apartment,
                'city' => $request->city,
                'province' => $request->province,
                'zip' => $request->zip,
            ]
        );

        // Store data in orders table

        $discountCodeId = NULL;
        $promoCode = '';
        $shipping = 0;
        $discount = 0;
        $subTotal = 0;

        // Retrieve cart items
        $cartItems = $request->cartItems;

        // Calculate subtotal
        foreach ($cartItems as $item) {
            $subTotal += $item['price'] * $item['quantity'];
        }

        // Apply discount if available
        if ($request->has('code')) {
            $code = DiscountCoupon::where('code', $request->code)->first();

            if ($code) {
                if ($code->type == 'percent') {
                    $discount = ($code->discount_amount / 100) * $subTotal;
                } else {
                    $discount = $code->discount_amount;
                }

                $discountCodeId = $code->id;
                $promoCode = $code->code;
            }
        }

        // Calculate shipping
        $shippingInfo = ShippingCharge::where('location_id', $request->location)->first();

        $totalQty = 0;
        foreach ($cartItems as $item) {
            $totalQty += $item['quantity'];
        }

        if ($shippingInfo) {
            $shipping = $totalQty * $shippingInfo->amount;
        }

        // Calculate grand total
        $grandTotal = ($subTotal - $discount) + $shipping;

        // Create order
        $order = new Order;
        $order->subtotal = $subTotal;
        $order->shipping = $shipping;
        $order->grand_total = $grandTotal;
        $order->discount = $discount;
        $order->coupon_code_id = $discountCodeId;
        $order->coupon_code = $promoCode;
        $order->payment_status = 'unpaid';
        $order->status = 'pending';
        $order->user_id = $user->id;
        $order->first_name = $request->first_name;
        $order->last_name = $request->last_name;
        $order->email = $request->email;
        $order->mobile = $request->mobile;
        $order->address = $request->address;
        $order->apartment = $request->apartment;
        $order->province = $request->province;
        $order->city = $request->city;
        $order->zip = $request->zip;
        $order->notes = $request->order_notes;
        $order->location_id = $request->location;
        $order->save();

        // Store order items in order items table
        foreach ($cartItems as $item) {
            $orderItem = new OrderItem;
            $orderItem->product_id = $item['id'];
            $orderItem->order_id = $order->id;
            $orderItem->name = $item['name'];
            $orderItem->qty = $item['quantity'];
            $orderItem->price = $item['price'];
            $orderItem->total = $item['price'] * $item['quantity'];
            $orderItem->save();

            // Update Product Stock
            $productData = Product::find($item['id']);
            if ($productData->track_qty == 'Yes') {
                $currentQty = $productData->qty;
                $updatedQty = $currentQty - $item['quantity'];
                $productData->qty = $updatedQty;
                $productData->save();
            }
        }

        // Send Order Email
        orderEmail($order->id, 'customer');

        return response()->json([
            'message' => 'Order saved successfully.',
            'orderId' => $order->id,
            'status' => true
        ]);
    }
}