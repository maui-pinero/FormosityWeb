@extends('front.layouts.app')

@section('content')
    <section class="container">
        <div class="col-md-12 text-center py-5">

            @if (Session::has('success'))
            <div class="alert alert-success">
                {{ Session::get('success') }}
            </div>
            @endif

            <h1>Thank You!</h1>
            <p>Your Order ID is: {{ $id }}</p>
            <a href="{{ route('front.shop') }}" class="btn bg-dgreen">Continue Shopping</a>
        </div>
    </section>
@endsection