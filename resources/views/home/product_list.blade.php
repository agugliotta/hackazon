@php
$j = 0;
$productCount = count($productListData['products']);
$lastProductNum = $productCount - 1;
$perRow = isset($perRow) && is_numeric($perRow) ? $perRow : 4;
$colClasses = $perRow == 3 ? 'col-md-4 col-lg-4' : 'col-md-3 col-lg-3';
@endphp
@if(!isset($productListData['hide_container']) || !$productListData['hide_container'])
    <div class="container product-list">
@endif

@foreach($productListData['products'] as $product)
    @php
    if ($product instanceof \App\Models\SpecialOffer) {
        $product = $product->product_offers;
    }
    $firstInRow = (0 == $j % $perRow && $j <= $lastProductNum);
    $lastInRow  = (0 == ($j + 1) % $perRow || $j == $lastProductNum);
    @endphp

    @if($firstInRow)
    <div class="row">
        <div class="col-xs-12">
            <div class="product-list-inline-large">
    @endif
                <div class="col-xs-12 col-sm-6 {{ $colClasses }}">
                    <div class="thumbnail light product-item" data-id="{{ $product->productID }}">
                        <div class="img-box">
                            <a href="/product/view?id={{ $product->productID }}">
                                <span class="label label-info price">${{ $product->Price }}</span>
                                <img class="img-home-portfolio" src="/products_pictures/{{ $product->picture }}" alt="{{ $product->name }}">
                            </a>
                        </div>
                        <div class="caption">
                            <a href="/product/view?id={{ $product->productID }}">{{ $product->name }}</a>
                        </div>
                        <div class="ratings">
                            <p>@include('common.rating_stars')</p>
                        </div>
                        <a class="btn btn-default btn-block" href="/category/view?id={{ $product->categoryID }}">all products in category</a>
                    </div>
                </div>
    @if($lastInRow)
            </div>
        </div>
    </div>
    @endif
    @php $j++; @endphp
@endforeach

@if(!isset($productListData['hide_container']) || !$productListData['hide_container'])
    </div>
@endif
