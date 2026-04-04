@extends('layouts.app')

@section('content')
<script>
    $(function () {
        var form = $("#cart_form"),
            link = $("#add_to_cart"),
            backBtn = $("#hw-back-btn");

        link.click(function (ev) {
            ev.preventDefault();
            var l = link.ladda();
            link.blur();
            $.ajax({
                url: form.attr('action'),
                data: form.serialize(),
                dataType: 'json',
                timeout: 15000,
                type: 'POST',
                beforeSend: function () {
                    link.attr('disabled', 'disabled');
                    l.ladda('start');
                },
                complete: function () {
                    link.removeAttr('disabled');
                    l.ladda('stop');
                }
            }).success(function (res) {
                link.removeAttr('disabled');
                link.blur();
                if (!(res && res.product)) { return; }
                addTopCartItem(res);
            });
            return false;
        });

        backBtn.click(function(e) {
            if (history.length > 1) {
                history.back();
                e.preventDefault();
            }
        });
    });
</script>

<div class="section">
    <div class="container">
        <div class="row">
            <div class="col-xs-9">
                <h1>{{ $product->name }}</h1>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                @include('common.multiple_breadcrumbs')
            </div>
        </div>
        <div class="row product-detail" data-id="{{ $product->productID }}">
            <div class="col-xs-12 col-sm-5 col-md-4">
                <a data-toggle="lightbox" data-title="{!! $product->name !!}"
                   href="/products_pictures/{!! $product->big_picture !!}">
                    <img class="img-responsive product-image img-thumbnail" src="/products_pictures/{!! $product->picture !!}" alt="">
                </a>
            </div>
            <div class="hidden-xs col-sm-2 col-md-1">
                <!-- Additional pictures -->
            </div>
            <div class="col-xs-12 col-sm-5 col-md-7">
                <div class="well">
                    <div class="row">
                        <div class="col-xs-6 col-sm-5 col-md-7">
                            @if(count($options) > 0)
                            <div class="option-variants">
                                @foreach($options as $variant)
                                    <strong>{{ $variant->parentOption->name }}:</strong> <span>{{ $variant->name }}</span><br>
                                @endforeach
                            </div>
                            @endif
                            <div class="ratings product-item-ratings">
                                <p class="pull-right">{{ $product->customer_votes }} reviews</p>
                                <p>
                                    @include('common.rating_stars')
                                    {!! $product->customers_rating !!} stars
                                </p>
                            </div>
                        </div>
                        <div class="col-xs-6 col-sm-7 col-md-5">
                            <span class="label label-important price">${{ $product->Price }}</span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <h3>Description</h3>
                            <p>{!! $product->description !!}</p>
                            @include('product._wishlist_button')
                        </div>
                    </div>
                    <hr>
                    <div class="row">
                        <div class="col-xs-12 col-md-3">
                            <a class="btn btn-block btn-default" href="/category/view?id={{ $product->categoryID }}" id="hw-back-btn"><span class="glyphicon glyphicon-chevron-left"></span>Back</a>
                        </div>
                        <div class="col-xs-12 col-md-6">
                            <form id="cart_form" action="/cart/add" method="post" class="form-horizontal" role="form">
                                <div class="form-group">
                                    <label for="count" class="col-xs-12 col-sm-3 col-md-3 col-lg-2 control-label">Count</label>
                                    <div class="col-xs-12 col-sm-9 col-md-9 col-lg-10">
                                        <div class="text-right">
                                            <input type="hidden" name="product_id" value="{{ $product->productID }}">
                                            <select class="form-control" id="qty" name="qty">
                                                <option>1</option>
                                                <option>2</option>
                                                <option>3</option>
                                                <option>4</option>
                                                <option>5</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="col-xs-12 col-md-3">
                            <a class="btn btn-block btn-primary ladda-button" id="add_to_cart" href="#" data-style="expand-right"
                               data-size="xs" data-spinner-size="16"><span class="glyphicon glyphicon-shopping-cart"></span> Add to cart</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="tabbable">
                    <ul class="nav nav-tabs">
                        <li class="active"><a href="#offers" data-toggle="tab">Special Offers</a></li>
                        <li class=""><a href="#bestsell" data-toggle="tab">Best selling products</a></li>
                    </ul>
                    <div class="tab-content">
                        <div class="row tab-pane offers-tab-pane active" id="offers">
                            @include('product.small_productlist')
                        </div>
                        <div class="row tab-pane bestsell-tab-pane" id="bestsell">
                            @include('product.big_productlist')
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-xs-12">
                <div class="well">
                    <div class="text-right">
                        @include('common.review_form')
                        <button class="btn btn-success" data-toggle="modal" data-target="#reviewForm">Leave a Review</button>
                    </div>
                    @foreach($product->getReviews() as $review)
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                @for($i = 1; $i < 6; $i++)
                                    @if($i > $review->rating)
                                        <span class="glyphicon glyphicon-star-empty"></span>
                                    @else
                                        <span class="glyphicon glyphicon-star"></span>
                                    @endif
                                @endfor
                                {!! $review->username !!}
                                <span class="pull-right">{{ $review->getDateLabel() }}</span>
                                <p>{!! $review->review !!}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
