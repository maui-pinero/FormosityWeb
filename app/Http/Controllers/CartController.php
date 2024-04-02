<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Gloudemans\Shoppingcart\Facades\Cart;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Location;
use App\Models\ShippingCharge;
use App\Models\CustomerAddress;
use App\Models\DiscountCoupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class CartController extends Controller

{
    public function addToCart(Request $request) {
        
        $product = Product::with('product_images')->find($request->id);

        if(Cart::content()->count() > 0) {
            // echo "Product already in cart.";
            // Products found in cart
            // Check if this product is already in the cart
            // Return as message that product is already in cart
            // if product not found in cart, then add product in cart

            $cartContent = Cart::content();
            $productAlreadyExist = false;

            foreach ($cartContent as $item) {
                if($item->id == $product->id) {
                    $productAlreadyExist = true;
                }
            }

            if ($productAlreadyExist == false) {
                Cart::add($product->id, $product->title, 1, $product->price, 
                ['productImage' => (!empty($product->product_images)) ? $product->product_images->first() : '']);
                
                $status = true;
                $message = '<strong>'.$product->title.'</strong> added to your cart successfully.';
                session()->flash('success',$message);

            } else {
                $status = false;
                $message = $product->title.' already added to cart';
            }

        } else {
            Cart::add($product->id, $product->title, 1, $product->price, 
            ['productImage' => (!empty($product->product_images)) ? $product->product_images->first() : '']);
            $status = true;
            $message = '<strong>'.$product->title.'</strong> added to your cart successfully.';
            session()->flash('success',$message);
        }

        return response()->json([
            'status' => $status,
            'message' => $message
        ]);
    }

    public function cart() {
        $cartContent = Cart::content();
        $data['cartContent'] = $cartContent;
        return view('front.cart',$data);
    }

    public function updateCart(Request $request) {
        $rowId = $request->rowId;
        $qty = $request->qty;

        $itemInfo = Cart::get($rowId);

        $product = Product::find($itemInfo->id);
        // check qty available in stock

        if ($product->track_qty == 'Yes') {
            if ($qty <= $product->qty) {
                Cart::update($rowId, $qty);
                $message = 'Cart updated successfully.';
                $status = true;
                session()->flash('success',$message);
            } else {
                $message = 'Requested quantity ('.$qty.') not available in stock.';
                $status = false;
                session()->flash('error',$message);
            }
        } else {
            Cart::update($rowId, $qty);
            $message = 'Cart updated successfully.';
            $status = true;
            session()->flash('success',$message);
        }

        return response()->json([
            'status' => $status,
            'message' => $message
        ]);
    }

    public function deleteItem(Request $request) {

        $itemInfo = Cart::get($request->rowId);

        if ($itemInfo == null) {
            $errorMessage = 'Item not found in cart.';
            session()->flash('error',$errorMessage);

            return response()->json([
                'status' => false,
                'message' => $errorMessage
            ]);
        }

        Cart::remove($request->rowId);

        $message = 'Item removed from cart successfully.';

        session()->flash('error',$message);

        return response()->json([
            'status' => true,
            'message' => $message
        ]);
    }

    public function checkout() {

        $discount = 0;

        // if cart is empty, redirect to cart page
        if(Cart::content()->count() == 0) {
            return redirect()->route('front.cart');
        }

        // if user is not logged in, redirect to login page
        if(Auth::check() == false) {

            if(!session()->has('url.intended')) {
                session(['url.intended' => url()->current()]);
            }

            return redirect()->route('account.login');
        }

        $customerAddress = CustomerAddress::where('user_id',Auth::user()->id)->first();

        session()->forget('url.intended');

        $locations = Location::orderBy('id','ASC')->get();

        $subTotal = Cart::subtotal(2,'.','');
        // Apply Discount here
        if (session()->has('code')) {

            $code = session()->get('code');

            if ($code->type == 'percent') {
                $discount = ($code->discount_amount/100)*$subTotal;
            } else {
                $discount = $code->discount_amount;
            }
        }

        // Calculate shipping here
        if ($customerAddress != '') {
            $userLocation = $customerAddress->location_id;
            $shippingInfo = ShippingCharge::where('location_id',$userLocation)->first();

            $totalQty = 0;
            $totalShippingCharge = 0;
            $grandTotal = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            $totalShippingCharge = $totalQty*$shippingInfo->amount;
            $grandTotal = ($subTotal-$discount)+$totalShippingCharge;

        } else {
            $grandTotal = ($subTotal-$discount);
            $totalShippingCharge = 0;

        }

        return view('front.checkout', [
            'locations' => $locations,
            'customerAddress' => $customerAddress,
            'totalShippingCharge' => $totalShippingCharge,
            'discount' => $discount,
            'grandTotal' => $grandTotal,
        ]);
    }

    public function processCheckout(Request $request) {
        
        // Apply validation

        $validator = Validator::make($request->all(),[
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

        if($validator->fails()) {
            return response()->json([
                'message' => 'Please fix the errors.',
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        // Save user address
        // $customerAddress = CustomerAddress::find();

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

        if($request->payment_method == 'cod') {

            $discountCodeId = NULL;
            $promoCode = '';
            $shipping = 0;
            $discount = 0;
            $subTotal = Cart::subtotal(2,'.','');

            // Apply Discount here
            if (session()->has('code')) {
                $code = session()->get('code');

                if ($code->type == 'percent') {
                    $discount = ($code->discount_amount/100)*$subTotal;
                } else {
                    $discount = $code->discount_amount;
                }

                $discountCodeId = $code->id;
                $promoCode = $code->code;
            }

            // Calculate shipping

            $shippingInfo = ShippingCharge::where('location_id',$request->location)->first();

            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }

            if ($shippingInfo != null) {
                $shipping = $totalQty*$shippingInfo->amount;
                $grandTotal = ($subTotal-$discount)+$shipping;
            }

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
            foreach (Cart::content() as $item) {
                $orderItem = new OrderItem;
                $orderItem->product_id = $item->id;
                $orderItem->order_id = $order->id;
                $orderItem->name = $item->name;
                $orderItem->qty = $item->qty;
                $orderItem->price = $item->price;
                $orderItem->total = $item->price*$item->qty;
                $orderItem->save();

                // Update Product Stock
                $productData = Product::find($item->id);
                if ($productData->track_qty == 'Yes') {
                    $currentQty = $productData->qty;
                    $updatedQty = $currentQty-$item->qty;
                    $productData->qty = $updatedQty;
                    $productData->save();
                }
            }

            // Send Order Email
            orderEmail($order->id,'customer');

            session()->flash('success','You have successfully placed your order.');

            Cart::destroy();

            session()->forget('code');

            return response()->json([
                'message' => 'Order saved successfully.',
                'orderId' => $order->id,
                'status' => true
            ]);
            
        } else {
            //
        }
    }

    public function thankyou($id) {

        return view('front.thanks',[
            'id' => $id
        ]);
    }

    public function getOrderSummary(Request $request) {

        $subTotal = Cart::subtotal(2,'.','');
        $discount = 0;
        $discountString = '';

        // Apply Discount here
        if (session()->has('code')) {
            $code = session()->get('code');

            if ($code->type == 'percent') {
                $discount = ($code->discount_amount/100)*$subTotal;
            } else {
                $discount = $code->discount_amount;
            }

            $discountString = '<div class="mt-4" id="discount-response">
                <strong>'.session()->get('code')->code.'</strong>
                <a class="btn btn-sm btn-danger" id="remove-discount"><i class="fa fa-times"></i></a>
            </div>';
        }

        if ($request->location_id > 0) {

            $shippingInfo = ShippingCharge::where('location_id',$request->location_id)->first();

            $totalQty = 0;
            foreach (Cart::content() as $item) {
                $totalQty += $item->qty;
            }
            
            if ($shippingInfo != null) {

                $shippingCharge = $totalQty*$shippingInfo->amount;
                $grandTotal = ($subTotal-$discount)+$shippingCharge;

                return response()->json([
                    'status' => true,
                    'grandTotal' => number_format($grandTotal,2),
                    'discount' => number_format($discount,2),
                    'discountString' => $discountString,
                    'shippingCharge' => number_format($shippingCharge,2),

                ]);
            }

        } else {

            return response()->json([
                'status' => true,
                'grandTotal' => number_format(($subTotal-$discount),2),
                'discount' => number_format($discount,2),
                'discountString' => $discountString,
                'shippingCharge' => number_format(0,2),
            ]);
        }
    }

    public function applyDiscount(Request $request) {
        // dd($request->code);

        $code = DiscountCoupon::where('code',$request->code)->first();

        if ($code == null) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid discount coupon.'
            ]);
        }

        // Check if coupon start date is valid or not

        $now = Carbon::now();

        // echo $now->format('Y-m-d H:i:s');

        if ($code->starts_at != "") {
            $startDate = Carbon::createFromFormat('Y-m-d H:i:s',$code->starts_at);

            if ($now->lt($startDate)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid discount coupon.'
                ]);
            }
        }

        if ($code->expires_at != "") {
            $expiryDate = Carbon::createFromFormat('Y-m-d H:i:s',$code->expires_at);

            if ($now->gt($expiryDate)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid discount coupon.'
                ]);
            }
        }

        // Max uses check

        if($code->max_uses > 0) {
            $couponUsed = Order::where('coupon_code_id',$code->id)->count();

            if ($couponUsed >= $code->max_uses) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid discount coupon.'
                ]);
            }
        }

        // Max uses user check

        if($code->max_uses_user > 0) {
            $couponUsedByUser = Order::where(['coupon_code_id' => $code->id, 'user_id' => Auth::user()->id])->count();

            if ($couponUsedByUser >= $code->max_uses_user) {
                return response()->json([
                    'status' => false,
                    'message' => 'You already used this coupon.'
                ]);
            }
        }

        $subTotal = Cart::subtotal(2,'.','');

        // Min amount condition check
        if ($code->min_amount > 0) {
            if ($subTotal < $code->min_amount) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your min amount must be $'.$code->min_amount.'.',
                ]);
            }
        }

        session()->put('code',$code);

        return $this->getOrderSummary($request);
    }

    public function removeCoupon(Request $request) {
        session()->forget('code');
        return $this->getOrderSummary($request);
    }
}