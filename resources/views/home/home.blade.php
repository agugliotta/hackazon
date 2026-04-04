@extends('layouts.app')

@section('content')
<div class="section home-top-section">
    <div class="container well">
        <div class="row">
            <div class="col-lg-12">
                <div class="col-lg-6 col-md-6">
                    @if(!auth()->user())
                        <h3><i class="fa fa-pencil"></i><a href="user/register"> Register on the site</a></h3>
                    @endif
                </div>
                <div class="col-lg-6 col-md-6">
                    <h3 style="text-align: right"><i class="fa fa-thumbs-up"></i><a href="bestprice"> Get the Best Price</a></h3>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="section home-top-product-section">
    <div class="container">
        <div class="row">
            <div class="col-xs-12 col-sm-8">
                <div class="row">
                    <div class="col-xs-9">
                        <h2>Special selection</h2>
                    </div>
                </div>

                <div class="row product-list-inline-small">
                    @foreach($special_offers as $specOffer)
                        @php $product = $specOffer->product_offers; @endphp
                        @if($product && $product->productID)
                            @include('home.product_item')
                        @endif
                    @endforeach
                </div>

                @php $selectedReviews = $selectedReviews ?? []; @endphp
                @include('home.top_reviews')
            </div>

            <div class="hidden-xs col-sm-4 col-md-4 col-lg-4">
                <br>
                @foreach($topProductBlocks as $productBlock)
                    @include('home.top_products_block')
                @endforeach

                <div class="row">
                    <div class="col-xs-12">
                        @include('common._side_slider_flash')
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@foreach($productSections as $sectionData)
    @if(count($sectionData['products']))
        @include('home.product_section')
    @endif
@endforeach

<div class="section-colored">
    @php $sectionData = ['title' => 'What Other Customers Are Looking At Right Now', 'products' => $otherCustomersProducts]; @endphp
    @include('home.product_section')
</div>

<div class="section">
    <div class="container">
        @if(!auth()->user())
        <div class="row well">
            <div class="col-lg-8 col-md-8">
                <h4>Sign up for mailing list and get the best products and best price!</h4>
            </div>
            <div class="col-lg-4 col-md-4">
                <a class="btn btn-lg btn-primary pull-right" href="/user/login">Sign up</a>
            </div>
        </div>
        @endif
    </div>
</div>

@include('home.category_list')
@endsection
