<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\SubCategory;
use App\Models\ProductRating;
use App\Models\Page;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\ContactEmail;

class ShopController extends Controller
{

    // RETRIEVE ALL CATEGORIES AND ITS SUB CATEGORIES AND PRODUCTS

    public function allCategories()
    {
        $categories = Category::orderBy('name', 'ASC')
            ->with('sub_category')
            ->where('status', 1)
            ->get();

        return response()->json([
            'categories' => $categories,
        ]);
    }

    // RETRIEVE ALL SUB CATEGORIES AND ITS PRODUCTS

    public function allSubCategories()
    {
        $subcategories = SubCategory::orderBy('name', 'ASC')
            ->where('status', 1)
            ->get();

        return response()->json([
            'subcategories' => $subcategories,
        ]);
    }

    // RETRIEVE ALL PRODUCTS

    public function allProducts() {
        $products = Product::with('product_images')->get();
    
        return response()->json([
            'products' => $products
        ]);
    }

    // RETRIEVE SINGLE CATEGORY AND ITS DETAILS

    public function category($categoryId)
    {
        try {
            $category = Category::with('sub_category.products')->findOrFail($categoryId);
            return response()->json([
                'category' => $category,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            return response()->json(['error' => 'Category not found'], 404);
        }
    }

    // RETRIEVE SINGLE SUBCATEGORY AND ITS DETAILS

    public function subcategory($subCategoryId)
    {
        try {
            $subcategory = SubCategory::with('products')->findOrFail($subCategoryId);
            return response()->json([
                'subcategory' => $subcategory,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            return response()->json(['error' => 'Sub Category not found'], 404);
        }
    }

    // RETRIEVE SINGLE PRODUCT AND ITS DETAILS

    public function product($productId)
    {
        try {
            $product = Product::where('id', $productId)
                ->withCount('product_ratings')
                ->withSum('product_ratings', 'rating')
                ->with('product_images', 'product_ratings')
                ->firstOrFail();

            $relatedProducts = [];
            if ($product->related_products != '') {
                $productArray = explode(',', $product->related_products);
                $relatedProducts = Product::whereIn('id', $productArray)
                    ->where('status', 1)
                    ->get();
            }

            $avgRating = '0.00';
            $avgRatingPer = 0;
            if ($product->product_ratings_count > 0) {
                $avgRating = number_format(($product->product_ratings_sum_rating / $product->product_ratings_count), 2);
                $avgRatingPer = ($avgRating * 100) / 5;
            }

            return response()->json([
                'product' => $product,
                'relatedProducts' => $relatedProducts,
                'avgRating' => $avgRating,
                'avgRatingPer' => $avgRatingPer,
            ]);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $exception) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    }

    // RETRIEVE FEATURED PRODUCTS

    public function featuredProducts() {
        $featuredProducts = Product::where('is_featured', 'Yes')
                                    ->where('status', 1)
                                    ->orderBy('id', 'DESC')
                                    ->take(8)
                                    ->get();
    
        return response()->json([
            'status' => true,
            'data' => $featuredProducts,
        ]);
    }

    // RETRIEVE LATEST PRODUCTS
    
    public function latestProducts() {
        $latestProducts = Product::where('status', 1)
                                  ->orderBy('id', 'DESC')
                                  ->take(8)
                                  ->get();
    
        return response()->json([
            'status' => true,
            'data' => $latestProducts,
        ]);
    }

    public function page($id) {
        $page = Page::find($id);
        
        if ($page == null) {
            return response()->json([
                'status' => false,
                'message' => 'Page not found.'
            ], 404);
        }
        
        return response()->json([
            'status' => true,
            'data' => $page
        ]);
    }

    // SEND CONTACT EMAIL FROM CONTACT US PAGE

    public function sendContactEmail(Request $request) {
        $validator = Validator::make($request->all(),[
            'name' => 'required',
            'email' => 'required|email',
            'subject' => 'required|min:10',
            'message' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $mailData = [
            'name' => $request->name,
            'email' => $request->email,
            'subject' => $request->subject,
            'message' => $request->message,
            'mail_subject' => 'You have received a contact email.',
        ];

        $admin = User::where('id',1)->first();

        Mail::to($admin->email)->send(new ContactEmail($mailData));

        return response()->json([
            'status' => true,
            'message' => 'Email sent successfully.'
        ]);
    }

    // POST RATING FOR A PRODUCT

    public function saveRating($productId, Request $request) {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:5',
            'email' => 'required|email',
            'comment' => 'required|min:10',
            'rating' => 'required',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
    
        $product = Product::find($productId);
        if (!$product) {
            return response()->json(['error' => 'Product not found'], 404);
        }
    
        $count = ProductRating::where('email', $request->email)
                               ->where('product_id', $productId)
                               ->count();
        if ($count > 0) {
            return response()->json(['error' => 'You already rated this product'], 422);
        }
    
        $productRating = new ProductRating;
        $productRating->product_id = $productId;
        $productRating->username = $request->name;
        $productRating->email = $request->email;
        $productRating->comment = $request->comment;
        $productRating->rating = $request->rating;
        $productRating->status = 0;
        $productRating->save();
    
        return response()->json(['message' => 'Thank you for leaving a review!'], 201);
    }    
}
