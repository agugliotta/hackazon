@extends('layouts.app')

@php
$isWishListOwner = isset($user) && $user->id() == $wishList->user_id;
@endphp

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">{{ $wishList->name }}</h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li><a href="/wishlist">Wish Lists</a></li>
                <li>{{ $wishList->name }}{{ $wishList->isDefault() ? ' (Default)' : '' }}</li>
            </ol>
        </div>
    </div>
    <div class="row wishlist"
        data-access="{{ $isWishListOwner ? 'owner' : 'guest' }}"
        data-id="{{ $wishList->id() }}"
        data-name="{{ htmlspecialchars($wishList->name) }}"
        data-type="{{ $wishList->type }}"
        data-token="{{ $controller->getToken('wishlist') }}">

        <div class="col-lg-3">
            @if(isset($user))
            <div class="collapsible-block js-wish-my-lists">
                <div class="block-header js-block-header">
                    <h4><a class="toggle">Your Wish Lists</a></h4>
                </div>
                <div class="block-content js-block-content">
                    <ul class="list-group">
                        @foreach($user->wishlists()->get() as $list)
                            <li class="list-group-item {{ $wishList->id() == $list->id ? 'list-group-item active' : '' }}">
                                <span class="badge">{{ $list->items()->count() }}</span>
                                <a href="/wishlist/view/{{ $list->id }}">{!! $list->name !!}</a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
            @endif

            @if(isset($user))
                @foreach($user->wishlistFollowers()->get() as $wishlistFollower)
                    @if($wishlistFollower->wishlists()->count() == 0) @continue @endif
                    <div class="collapsible-block js-wish-my-lists" data-id="{{ $wishlistFollower->id }}">
                        <hr />
                        <div class="block-header js-block-header">
                            <h4><a class="toggle">{{ $wishlistFollower->username }}</a></h4>
                        </div>
                        <div class="block-content js-block-content" style="{{ $wishList->user_id != $wishlistFollower->id ? 'display: none' : '' }}">
                            <ul class="list-group">
                                @foreach($wishlistFollower->wishlists()->get() as $list)
                                    @if(\App\Models\Wishlist::TYPE_PUBLIC != $list->type) @continue @endif
                                    <li class="list-group-item {{ $wishList->id() == $list->id ? 'list-group-item active' : '' }}">
                                        <span class="badge">{{ $list->items()->count() }}</span>
                                        <a href="/wishlist/view/{{ $list->id }}">{!! $list->name !!}</a>
                                    </li>
                                @endforeach
                            </ul>
                            <a class="remove_follower" onclick="return false;" href="javascript:void(0);"><span class="glyphicon glyphicon-remove" style="color:#d58512"></span> Remove person</a>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="col-lg-9">
            <div class="clearfix">
                @include('wishlist._search_form')
                <div class="btn-group pull-right top-buttons">
                    @if(isset($user))<button type="button" class="btn btn-default js-add-wish-list">Add Wish List</button>@endif
                    @if($isWishListOwner)
                        <button type="button" class="btn btn-default js-edit-wish-list">Edit</button>
                        <button type="button" class="btn btn-default js-delete-wish-list">Delete</button>
                    @endif
                </div>
            </div>

            <div class="products clearfix">
                <div class="clearfix product-list">
                    @php
                    $productListData = ['products' => $products, 'hide_container' => true];
                    $perRow = 3;
                    $productPages = ceil($productCount / $perPage);
                    @endphp
                    @include('home.product_list')
                </div>

                @if($productPages > 1)
                    <ul class="pagination pull-right clearfix">
                        <li><a href="/wishlist/view/{{ $wishList->id() . ($page > 1 ? '?page='.max(1, $page - 1) : '') }}" class="{{ $page == 1 ? 'disabled' : '' }}">&laquo;</a></li>
                        @for($iPage = 1; $iPage <= $productPages; $iPage++)
                            <li {{ $iPage == $page ? 'class="active"' : '' }}>
                                <a href="/wishlist/view/{{ $wishList->id() . ($iPage == 1 ? '' : '?page='.$iPage) }}">{{ $iPage }}</a>
                            </li>
                        @endfor
                        <li><a href="/wishlist/view/{{ $wishList->id() . ($page < $productPages ? '?page='.min($productPages, $page + 1) : '') }}" class="{{ $page == $productPages ? 'disabled' : '' }}">&raquo;</a></li>
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>

@include('wishlist._add_form')
@endsection
