<div class="col-xs-12 col-sm-12 col-md-4 col-lg-4">
    <div class="thumbnail">
        <div class="img-box">
            <a href="/product/view?id={{ $product->productID }}"><img class="img-responsive img-home-portfolio"
                    src="/products_pictures/{{ $product->picture }}" alt="{{ $product->name }}"></a>
        </div>
        <div class="caption">
            <a href="/product/view?id={{ $product->productID }}" title="{{ $product->name }}">{{ \Illuminate\Support\Str::limit($product->name, 50, '') }}</a>
            <p class="product-annotation">
                <span class="text-block" title="{{ $product->description }}">{{ \Illuminate\Support\Str::limit($product->description, 60, '') }}</span>
                <span class="label label-info price pull-right">${{ $product->Price }}</span>
            </p>
        </div>
    </div>
</div>
