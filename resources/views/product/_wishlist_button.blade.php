@php
$userObj = auth()->user();
$userWishLists = $userObj ? $userObj->wishlists()->get()->all() : [];
@endphp
<div class="wish-list-button-block js-wish-list-button-block">
    @if($userObj)
        @php $userDefaultWishList = \App\Models\Wishlist::getUserDefaultWishList($userObj); @endphp
        @if(!$product->isInUserWishList($userObj))
            @if(count($userWishLists) >= 2)
                <div class="dropdown pull-right add-to-wish-list-dropdown">
                    <button class="btn btn-default dropdown-toggle" type="button" id="addProduct_{{ $product->productID }}ToWishList" data-toggle="dropdown">
                        Add To Wish List
                        <span class="caret"></span>
                    </button>
                    <ul class="dropdown-menu" role="menu" aria-labelledby="addProduct_{{ $product->productID }}ToWishList">
                        @foreach($userWishLists as $userWishList)
                            <li role="presentation"><a role="menuitem" tabindex="-1" href="#"
                                    class="js-add-to-wish-list" data-id="{{ $product->productID }}"
                                    data-wishlist-id="{{ $userWishList->id }}">
                                {{ $userWishList->name }}
                            </a></li>
                        @endforeach
                    </ul>
                </div>
            @else
                <a href="#" class="btn btn-default pull-right js-add-to-wish-list" data-id="{{ $product->productID }}"
                    data-wishlist-id="{{ $userDefaultWishList ? $userDefaultWishList->id : '' }}">Add To Wish List</a>
            @endif
        @else
            <a href="#" class="btn btn-warning pull-right js-remove-from-wish-list" data-id="{{ $product->productID }}">Remove From Wish List</a>
        @endif
    @else
        <a href="/user/login?return_url={{ rawurlencode(request()->server('REQUEST_URI')) }}" class="btn btn-default pull-right">Add To Wish List</a>
    @endif
</div>
