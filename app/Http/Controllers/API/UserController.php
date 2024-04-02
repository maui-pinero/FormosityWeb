<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\Location;
use App\Models\CustomerAddress;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Wishlist;
use App\Models\Product;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordEmail;

class UserController extends Controller
{

    // LOGIN

    public function login(Request $request) {

        $validator = Validator::make($request -> all(),[
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if($validator->fails()){
            return response()->json([
                'message' => 'Validation Fails',
                'errors' => $validator->errors()
            ],422);
        }

        $user = User::where('email',$request->email)->first();

        if ($user){
            if(Hash::check($request->password,$user->password)){
                $token=$user->createToken('auth-token')->plainTextToken;
                return response()->json([
                    'message' => 'Login Successful',
                    'token' => $token,
                    'data' => $user
                ],200);
            }
            else{
                return response()->json([
                    'message' => 'Incorrect Credentials',
                ],400);
            }
        }
        else{
            return response()->json([
                'message' => 'Incorrect Credentials'
            ],400);
        }
    }

    // REGISTER

    public function register(Request $request) {

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'password' => 'required',
            'confirm_password' => 'required|same:password',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fails',
                'errors' => $validator->errors()
            ], 422);
        }
    
        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password)
        ]);
        $user->role = 1;
        $user->save();
    
        return response()->json([
            'message' => 'Registered Successfully',
            'data' => $user
        ], 200);
    }

    // LOGOUT

    public function logout(Request $request) {

        $request->user()->currentAccessToken()->delete();
        return response()->json([
            'message' => 'User Successfully Logged Out',
            'data' =>$request->user()
        ],200);
    }

    // USER

    public function user(Request $request){
        return response()->json([
            'message' => 'User Successfully Fetched',
            'data' => $request->user()
        ],200);
    }

    // UPDATE USER PROFILE

    public function updateProfile(Request $request) {

        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation Fails',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
        ]);

        return response()->json([
            'message' => 'Profile Updated Successfully',
            'data' => $user
        ], 200);
    }

    // CREATE OR UPDATE USER DELIVERY ADDRESS

    public function updateAddress(Request $request) 
    {
        $userId = Auth::user()->id;
        
        $validator = Validator::make($request->all(),[
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'location' => 'required|exists:locations,name',
            'address' => 'required',
            'city' => 'required',
            'province' => 'required',
            'zip' => 'required',
            'mobile' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $locationId = Location::where('name', $request->location)->value('id');

        CustomerAddress::updateOrCreate(
            ['user_id' => $userId],
            [
                'user_id' => $userId,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'mobile' => $request->mobile,
                'location_id' => $locationId,
                'address' => $request->address,
                'apartment' => $request->apartment,
                'city' => $request->city,
                'province' => $request->province,
                'zip' => $request->zip,
            ]
        );

        return response()->json([
            'status' => true,
            'message' => 'Address updated successfully.'
        ], 200);
    }

    // ORDERS

    public function orders(Request $request) {

        $user = $request->user();
    
        $orders = Order::where('user_id', $user->id)->orderBy('created_at', 'DESC')->get();
    
        return response()->json([
            'message' => 'Orders fetched successfully',
            'orders' => $orders,
        ], 200);
    }

    // FETCH ORDER DETAILS BY ID

    public function orderDetail(Request $request, $id) {

        $user = $request->user();
        $data = [];
        $order = Order::where('user_id', $user->id)->where('id', $id)->first();
        $data['order'] = $order;
    
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
    
        $orderItems = OrderItem::where('order_id', $id)->get();
        $data['orderItems'] = $orderItems;
    
        $orderItemsCount = $orderItems->count();
        $data['orderItemsCount'] = $orderItemsCount;
    
        return response()->json($data, 200);
    }

    // CANCEL ORDER BY ID

    public function cancelOrder(Request $request, $id) {

        $user = $request->user();
        $order = Order::find($id);
    
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
    
        if ($order->user_id != $user->id || in_array($order->status, ['shipped', 'delivered'])) {
            return response()->json(['message' => 'You are not authorized to cancel this order.'], 403);
        }
    
        $order->status = 'cancelled';
        $order->save();
    
        return response()->json(['message' => 'Order cancelled successfully', 'order' => $order], 200);
    }

    // WISHLIST

    public function wishlist(Request $request) {

        $user = $request->user();
        $wishlists = Wishlist::where('user_id', $user->id)->get();
    
        return response()->json(['wishlists' => $wishlists], 200);
    }

    // ADD TO WISHLIST

    public function addToWishlist(Request $request) {

        if (Auth::check() == false) {
            return response()->json(['status' => false, 'message' => 'User is not authenticated.'], 401);
        }
    
        $product = Product::find($request->id);
    
        if ($product == null) {
            return response()->json(['status' => false, 'message' => 'Product not found.'], 404);
        }
    
        if (Auth::user()->wishlist()->where('product_id', $request->id)->exists()) {
            return response()->json(['status' => false, 'message' => 'Product already exists in the wishlist.'], 400);
        }
    
        Wishlist::create([
            'user_id' => Auth::user()->id,
            'product_id' => $request->id
        ]);
    
        return response()->json([
            'status' => true,
            'message' => 'Product added to the wishlist successfully.',
            'product' => $product
        ], 200);
    }
    
    // REMOVE FROM WISHLIST

    public function removeProductFromWishlist(Request $request) {
        $user = $request->user();
        $wishlist = Wishlist::where('user_id', $user->id)
                            ->where('product_id', $request->id)
                            ->first();
    
        if ($wishlist == null) {
            return response()->json(['error' => 'Product already removed.'], 404);
        } else {
            $wishlist->delete();
            return response()->json(['message' => 'Product removed successfully.'], 200);
        }
    }

    // CHANGE PASSWORD

    public function changePassword(Request $request) {
        
        $validator = Validator::make($request->all(), [
            'old_password' => 'required',
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->passes()) {
            $user = User::find(Auth::id());

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Your old password is incorrect, please try again.',
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password changed successfully.',
            ]);
        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }
    }

    // FORGOT PASSWORD

    public function forgotPassword(Request $request) {

        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $token = Str::random(60);

        \DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        \DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        $user = User::where('email', $request->email)->first();

        $formData = [
            'token' => $token,
            'user' => $user,
            'mailSubject' => 'You have requested to reset your password.',
        ];

        Mail::to($request->email)->send(new ResetPasswordEmail($formData));

        return response()->json([
            'status' => true,
            'message' => 'Please check your inbox to reset your password.',
        ]);
    }

    // RESET PASSWORD

    public function resetPassword(Request $request, $token)
    {
        $tokenObj = \DB::table('password_reset_tokens')->where('token', $token)->first();

        if ($tokenObj == null) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid request.',
            ], 400);
        }

        $user = User::where('email', $tokenObj->email)->first();

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        \DB::table('password_reset_tokens')->where('email', $user->email)->delete();

        return response()->json([
            'status' => true,
            'message' => 'Password updated successfully.',
        ]);
    }
}
