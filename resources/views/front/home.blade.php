@extends('front.layouts.app')

@section('content')
<section class="section-1">
    <div id="carouselExampleIndicators" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-interval="false">
        <div class="carousel-inner">
            <div class="carousel-item active">
                <!-- <img src="images/carousel-1.jpg" class="d-block w-100" alt=""> -->

                <picture>
                    <source media="(max-width: 799px)" srcset="{{ asset('front-assets/images/carousel-1-m.png') }}" />
                    <source media="(min-width: 800px)" srcset="{{ asset('front-assets/images/carousel-1.png') }}" />
                    <img src="{{ asset('front-assets/images/carousel-1.png') }}" alt="" />
                </picture>

                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                    <div class="p-3">
                        <h1 class="display-4 text-white mb-3">Welcome to Formosity</h1>
                        <p class="mx-md-5 px-5">The most trusted online flower shop for your floral needs!</p>
                        <a class="btn btn-outline-light py-2 px-4 mt-3" href="{{ route('front.shop') }}">Shop Now</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">

                <picture>
                    <source media="(max-width: 799px)" srcset="{{ asset('front-assets/images/carousel-2-m.jpg') }}" />
                    <source media="(min-width: 800px)" srcset="{{ asset('front-assets/images/carousel-2.jpg') }}" />
                    <img src="{{ asset('front-assets/images/carousel-2.jpg') }}" alt="" />
                </picture>

                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                    <div class="p-3">
                        <h1 class="display-4 text-white mb-3">First Time Ordering?</h1>
                        <p class="mx-md-5 px-5">Use the exclusive Formosity promo code: WELCOME to get 10% off on your first order! Don't miss out!</p>
                        <a class="btn btn-outline-light py-2 px-4 mt-3" href="{{ route('front.shop') }}">Shop Now</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">

                <picture>
                    <source media="(max-width: 799px)" srcset="{{ asset('front-assets/images/carousel-3-m.jpg') }}" />
                    <source media="(min-width: 800px)" srcset="{{ asset('front-assets/images/carousel-3.jpg') }}" />
                    <img src="{{ asset('front-assets/images/carousel-3.jpg') }}" alt="" />
                </picture>

                <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                    <div class="p-3">
                        <h1 class="display-4 text-white mb-3">Variety of Blooms</h1>
                        <p class="mx-md-5 px-5">Surprise your loved ones with Formosity's flowers.</p>
                        <a class="btn btn-outline-light py-2 px-4 mt-3" href="{{ route('front.shop') }}">Shop Now</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#carouselExampleIndicators" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
    </div>
</section>
<section class="section-2">
    <div class="container">
        <div class="row">
            <div class="col-lg-3">
                <div class="box shadow-lg">
                    <div class="fa icon fa-hand-holding-heart text-green m-0 mr-3"></div>
                    <h2 class="font-weight-semi-bold m-0">Freshness Guaranteed</h5>
                </div>                    
            </div>
            <div class="col-lg-3 ">
                <div class="box shadow-lg">
                    <div class="fa icon fa-heart text-purple m-0 mr-3"></div>
                    <h2 class="font-weight-semi-bold m-0">Wide Selection</h2>
                </div>                    
            </div>
            <div class="col-lg-3">
                <div class="box shadow-lg">
                    <div class="fa icon fa-exchange-alt text-pink m-0 mr-3"></div>
                    <h2 class="font-weight-semi-bold m-0">Same-Day Delivery</h2>
                </div>                    
            </div>
            <div class="col-lg-3 ">
                <div class="box shadow-lg">
                    <div class="fa icon fa-phone-volume text-blue m-0 mr-3"></div>
                    <h2 class="font-weight-semi-bold m-0">24/7 Customer Support</h5>
                </div>                    
            </div>
        </div>
    </div>
</section>
<section class="section-3">
    <div class="container">
        <div class="section-title">
            <h2>Categories</h2>
        </div>           
        <div class="row pb-3">
            @if (getCategories()->isNotEmpty())
            @foreach (getCategories() as $category)
            <div class="col-lg-3">
                <div class="cat-card">
                    <div class="left">
                        @if ($category->image != "")
                        <img src="{{ asset('uploads/category/thumb/'.$category->image) }}" alt="" class="img-fluid">
                        @endif
                        <!-- <img src="{{ asset('front-assets/images/cat-1.jpg') }}" alt="" class="img-fluid"> -->
                    </div>
                    <div class="right">
                        <div class="cat-data">
                            <h2>{{ $category->name }}</h2>
                            <!-- <p>100 Products</p> -->
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
            @endif
        </div>
    </div>
</section>

<section class="section-4 pt-5">
    <div class="container">
        <div class="section-title">
            <h2>Featured Products</h2>
        </div>    
        <div class="row pb-3">
            @if ($featuredProducts->isNotEmpty())
                @foreach ($featuredProducts as $product)
                @php
                    $productImage = $product->product_images->first();
                @endphp
                <div class="col-md-3">
                    <div class="card product-card">
                        <div class="product-image position-relative">
                            <a href="{{ route("front.product",$product->slug) }}" class="product-img">

                                @if (!empty($productImage->image))
                                <img class="card-img-top" src="{{ asset('uploads/product/small/'.$productImage->image) }}" />
                                @else
                                <img src="{{ asset('admin-assets/img/default-150x150.png') }}" />
                                @endif

                            </a>

                            <a onclick="addToWishlist({{ $product->id }})" class="wishlist" href="javascript:void(0);"><i class="far fa-heart"></i></a>                            

                            <div class="product-action">
                                @if ($product->track_qty == 'Yes')
                                    @if ($product->qty > 0)
                                    <a class="btn btn-dark" href="javascript:void(0);" onclick="addToCart({{ $product->id }});">
                                        <i class="fa fa-shopping-cart"></i> Add to Cart
                                    </a>
                                    @else
                                    <a class="btn btn-dark" href="javascript:void(0);">
                                        Out of Stock
                                    </a>
                                    @endif
                                @else
                                <a class="btn btn-dark" href="javascript:void(0);" onclick="addToCart({{ $product->id }});">
                                    <i class="fa fa-shopping-cart"></i> Add to Cart
                                </a>
                                @endif
                            </div>
                        </div>                        
                        <div class="card-body text-center mt-3">
                            <a class="h6 link" href="{{ route("front.product",$product->slug) }}">{{ $product->title }}</a>
                            <div class="price mt-2">

                                <span class="h5"><strong>${{ $product->price }}</strong></span>
                                @if($product->compare_price > 0)
                                <span class="h6 text-underline"><del>${{ $product->compare_price }}</del></span>
                                @endif

                            </div>
                        </div>                        
                    </div>                                               
                </div>
                @endforeach
            @endif               
        </div>
    </div>
</section>

<section class="section-4 pt-5">
    <div class="container">
        <div class="section-title">
            <h2>Latest Products</h2>
        </div>    
        <div class="row pb-3">
            @if ($latestProducts->isNotEmpty())
                @foreach ($latestProducts as $product)
                @php
                    $productImage = $product->product_images->first();
                @endphp
                <div class="col-md-3">
                    <div class="card product-card">
                        <div class="product-image position-relative">
                            <a href="{{ route("front.product",$product->slug) }}" class="product-img">

                                @if (!empty($productImage->image))
                                <img class="card-img-top" src="{{ asset('uploads/product/small/'.$productImage->image) }}" />
                                @else
                                <img src="{{ asset('admin-assets/img/default-150x150.png') }}" />
                                @endif

                            </a>

                            <a onclick="addToWishlist({{ $product->id }})" class="wishlist" href="javascript:void(0);"><i class="far fa-heart"></i></a>                            

                            <div class="product-action">
                                @if ($product->track_qty == 'Yes')
                                    @if ($product->qty > 0)
                                    <a class="btn btn-dark" href="javascript:void(0);" onclick="addToCart({{ $product->id }});">
                                        <i class="fa fa-shopping-cart"></i> Add to Cart
                                    </a>
                                    @else
                                    <a class="btn btn-dark" href="javascript:void(0);">
                                        Out of Stock
                                    </a>
                                    @endif
                                @else
                                <a class="btn btn-dark" href="javascript:void(0);" onclick="addToCart({{ $product->id }});">
                                    <i class="fa fa-shopping-cart"></i> Add to Cart
                                </a>
                                @endif
                            </div>
                        </div>                        
                        <div class="card-body text-center mt-3">
                            <a class="h6 link" href="{{ route("front.product",$product->slug) }}">{{ $product->title }}</a>
                            <div class="price mt-2">

                                <span class="h5"><strong>${{ $product->price }}</strong></span>
                                @if($product->compare_price > 0)
                                <span class="h6 text-underline"><del>${{ $product->compare_price }}</del></span>
                                @endif

                            </div>
                        </div>                        
                    </div>                                               
                </div>
                @endforeach
            @endif            
        </div>
    </div>
</section>
@endsection