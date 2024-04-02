<?php

use App\Mail\OrderEmail;
use Illuminate\Support\Facades\Mail;
use App\Models\Category;
use App\Models\ProductImage;
use App\Models\Order;
use App\Models\Location;
use App\Models\Page;

function getCategories(){
    return Category::orderBy('name','ASC')
            ->with('sub_category')
            ->orderBy('id','DESC')
            ->where('status',1)
            ->where('showHome','Yes')
            ->get();
}

function getProductImage($productId) {
    return ProductImage::where('product_id',$productId)->first();
}

function orderEmail($orderId, $userType="customer") {
    $order = Order::where('id',$orderId)->with('items')->first();

    if ($userType == 'customer') {
        $subject = 'Thanks for your order!';
        $email = $order->email;
    } else {
        $subject = 'You have received an order!';
        $email = env('ADMIN_EMAIL');
    }

    $mailData = [
        'subject' => $subject,
        'order' => $order,
        'userType' => $userType
    ];

    Mail::to($email)->send(new OrderEmail($mailData));
}

function getLocationInfo($id) {
    return Location::where('id',$id)->first();
}

function staticPages() {
    $pages = Page::orderBy('name','ASC')->get();
    return $pages;
}

?>