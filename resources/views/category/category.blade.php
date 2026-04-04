@extends('layouts.app')

@section('content')
<div class="section">
    <div class="container category-products">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">{{ $pageTitle }}</h1>
                @include('common.breadcrumbs')
            </div>
        </div>

        @if(count($subCategories) > 0)
            @php
            $rows = count($subCategories) % 4 == 0 ? count($subCategories) / 4 : ceil(count($subCategories) / 4);
            $subCatsWork = $subCategories;
            @endphp
            @for($r = 0; $r < $rows; $r++)
                <div class="row">
                    <div class="col-xs-12">
                        <div class="product-list-inline-large">
                            @php
                            $count = count($subCatsWork) < 4 ? count($subCatsWork) : 4;
                            $itemClass = 'light';
                            @endphp
                            @for($cnt = 0; $cnt < $count; $cnt++)
                                @php
                                $item = array_shift($subCatsWork);
                                $product = $item->products()->limit(1)->first();
                                @endphp
                                @if(!$product)
                                    @include('category._category_empty_portret')
                                @else
                                    @include('category._category_portret')
                                @endif
                                @php $itemClass = $itemClass == 'light' ? 'dark' : 'light'; @endphp
                            @endfor
                        </div>
                    </div>
                </div>
            @endfor
        @else
            <div class="row">
                <div class="col-xs-12">
                    <div class="product-list-inline-large">
                        @php $itemClass = 'light'; $index = 0; @endphp
                        @foreach($products as $product)
                            <div class="col-xs-12 col-sm-6 col-md-3 col-lg-3">
                                <div class="thumbnail {{ $itemClass }}">
                                    <div class="img-box">
                                        <a href="/product/view?id={{ $product->productID }}">
                                            <div class="label label-info price">$ {{ $product->Price }}</div>
                                            <img class="category-list-product" data-hover="/products_pictures/{{ $product->picture }}" src="/products_pictures/{{ $product->picture }}" alt="">
                                        </a>
                                    </div>
                                    <div class="caption">
                                        <a href="/product/view?id={{ $product->productID }}">{{ $product->name }}</a>
                                    </div>
                                    <div class="ratings">
                                        <p>@include('common.rating_stars')</p>
                                    </div>
                                </div>
                            </div>
                            @php
                            if (($index + 1) % 4 == 0 || $index == count($products) - 1) {
                                echo "<div class=\"clearfix\"></div>";
                            }
                            $index++;
                            $itemClass = $itemClass == 'light' ? 'dark' : 'light';
                            @endphp
                        @endforeach
                    </div>
                    {!! $pager->links() !!}
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
