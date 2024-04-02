<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Wishlist;
use App\Models\Location;
use App\Models\CustomerAddress;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordEmail;

class AuthController extends Controller
{
    public function login() {
        return view('front.account.login');
    }

    public function register() {
        return view('front.account.register');
    }

    public function processRegister(Request $request) {

        $validator = Validator::make($request->all(),[
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users',
            'phone' => 'required',
            'password' => 'required|min:5|confirmed'
        ]);

        if ($validator->passes()) {

            $user = new User;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->password = Hash::make($request->password);
            $user->save();

            session()->flash('success','You have been registered successfully.');

            return response()->json([
                'status' => true
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function authenticate(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email',
            'password' => 'required'
        ]);

        if ($validator->passes()) {

            if (Auth::attempt(['email' => $request->email, 'password' => $request->password], $request->get('remember'))) {

                // if(!session()->has('url.intended')) {
                //     return redirect(session()->get('url.intended'));
                // }

                return redirect()->route('front.home');

            } else {
                // session()->flash('error','Email or password is incorrect.');
                return redirect()->route('account.login')
                        ->withInput($request->only('email'))
                        ->with('error','Email or password is incorrect.');
            }

        } else {
            return redirect()->route('account.login')
            ->withErrors($validator)
            ->withInput($request->only('email'));
        }
    }

    public function profile() {

        $userId = Auth::user()->id;

        $locations = Location::orderBy('name','ASC')->get();

        $user = User::where('id',Auth::user()->id)->first();

        $address = CustomerAddress::where('user_id',$userId)->first();

        return view('front.account.profile',[
            'user' => $user,
            'locations' => $locations,
            'address' => $address,
        ]);
    }

    public function updateProfile(Request $request) {
        $userId = Auth::user()->id;
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email|unique:users,email,'.$userId.',id',
            'phone' => 'required',
        ]);

        if ($validator->passes()) {
            $user = User::find($userId);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->phone = $request->phone;
            $user->save();

            session()->flash('success','Profile updated successfully.');

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.'
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function updateAddress(Request $request) {
        $userId = Auth::user()->id;
        
        $validator = Validator::make($request->all(),[
            'first_name' => 'required',
            'last_name' => 'required',
            'email' => 'required',
            'location_id' => 'required',
            'address' => 'required',
            'city' => 'required',
            'province' => 'required',
            'zip' => 'required',
            'mobile' => 'required'
        ]);

        if ($validator->passes()) {
            // $user = User::find($userId);
            // $user->name = $request->name;
            // $user->email = $request->email;
            // $user->phone = $request->phone;
            // $user->save();

            CustomerAddress::updateOrCreate(
                ['user_id' => $userId],
                [
                    'user_id' => $userId,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'email' => $request->email,
                    'mobile' => $request->mobile,
                    'location_id' => $request->location_id,
                    'address' => $request->address,
                    'apartment' => $request->apartment,
                    'city' => $request->city,
                    'province' => $request->province,
                    'zip' => $request->zip,
                ]
            );

            session()->flash('success','Address updated successfully.');

            return response()->json([
                'status' => true,
                'message' => 'Profile updated successfully.'
            ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function logout() {
        Auth::logout();
        return redirect()->route('account.login')
        ->with('success','Logged out successfully!');
    }

    public function orders() {

        $user = Auth::user();

        $orders = Order::where('user_id',$user->id)->orderBy('created_at','DESC')->get();

        $data['orders'] = $orders;

        return view('front.account.order',$data);
    }

    public function orderDetail($id) {

        $user = Auth::user();
        $data = [];
        $order = Order::where('user_id',$user->id)->where('id',$id)->first();
        $data['order'] = $order;

        $orderItems = OrderItem::where('order_id',$id)->get();
        $data['orderItems'] = $orderItems;

        $orderItemsCount = OrderItem::where('order_id',$id)->count();
        $data['orderItemsCount'] = $orderItemsCount;

        return view('front.account.order-detail',$data);
    }

    public function cancelOrder($id) {
        
        $order = Order::find($id);
    
        if ($order->user_id != Auth::id() || in_array($order->status, ['shipped', 'delivered'])) {
            return redirect()->back()->with('error', 'You are not authorized to cancel this order.');
        }
    
        $order->status = 'cancelled';
        $order->save();
    
        return redirect()->route('account.orderDetail', $order->id)->with('success', 'Order cancelled successfully.');
    }

    public function wishlist() {

        $wishlists = Wishlist::where('user_id',Auth::user()->id)->get();

        $data = [];
        $data['wishlists'] = $wishlists;

        return view('front.account.wishlist',$data);
    }

    public function removeProductFromWishlist(Request $request) {
        $wishlist = Wishlist::where('user_id',Auth::user()->id)->where('product_id',$request->id)->first();
        if ($wishlist == null) {
            session()->flash('error','Product already removed.');
            return response()->json([
                'status' => true,
            ]);
        } else {
            Wishlist::where('user_id',Auth::user()->id)->where('product_id',$request->id)->delete();
            session()->flash('success','Product removed successfully.');
            return response()->json([
                'status' => true,
            ]);
        }
    }

    public function showChangePasswordForm() {
        return view('front.account.change-password');
    }

    public function changePassword(Request $request) {
        $validator = Validator::make($request->all(),[
            'old_password' => 'required',
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password',
        ]);

        if ($validator->passes()) {

            $user = User::select('id','password')->where('id', Auth::user()->id)->first();

            if (!Hash::check($request->old_password,$user->password)) {
                session()->flash('error','Your old password is incorrect, please try again.');
                return response()->json([
                    'status' => true,
                ]);
            }

            User::where('id',$user->id)->update([
                'password' => Hash::make($request->new_password)
            ]);

            session()->flash('success','Password changed successfully.');
                return response()->json([
                    'status' => true,
                ]);

        } else {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }
    }

    public function forgotPassword() {
        return view('front.account.forgot-password');
    }

    public function processForgotPassword(Request $request) {
        $validator = Validator::make($request->all(),[
            'email' => 'required|email|exists:users,email'
        ]);

        if ($validator->fails()) {
            return redirect()->route('front.forgotPassword')->withInput()->withErrors($validator);
        }

        $token = Str::random(60);

        \DB::table('password_reset_tokens')->where('email',$request->email)->delete();

        \DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => now(),
        ]);

        // Send Email Here

        $user = User::where('email',$request->email)->first();

        $formData = [
            'token' => $token,
            'user' => $user,
            'mailSubject' => 'You have requested to reset your password.'
        ];

        Mail::to($request->email)->send(new ResetPasswordEmail($formData));

        return redirect()->route('front.forgotPassword')->with('success','Please check your inbox to reset your password.');
    }

    public function resetPassword($token) {

        $tokenExist = \DB::table('password_reset_tokens')->where('token',$token)->first();

        if ($tokenExist == null) {
            return redirect()->route('front.forgotPassword')->with('error','Invalid request.');
        }

        return view('front.account.reset-password',[
            'token' => $token
        ]);
    }

    public function processResetPassword(Request $request) {
        $token = $request->token;

        $tokenObj = \DB::table('password_reset_tokens')->where('token',$token)->first();

        if ($tokenObj == null) {
            return redirect()->route('front.forgotPassword')->with('error','Invalid request.');
        }

        $user = User::where('email',$tokenObj->email)->first();

        $validator = Validator::make($request->all(),[
            'new_password' => 'required|min:5',
            'confirm_password' => 'required|same:new_password'
        ]);

        if ($validator->fails()) {
            return redirect()->route('front.resetPassword',$token)->withErrors($validator);
        }

        User::where('id',$user->id)->update([
            'password' => Hash::make($request->new_password)
        ]);

        \DB::table('password_reset_tokens')->where('email',$user->email)->delete();

        return redirect()->route('account.login')->with('success','You have updated your password successfully.');
    }
}
