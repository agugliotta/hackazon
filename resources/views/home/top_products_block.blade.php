<div class="row">
    <div class="col-xs-12">
        <div class="well well-small">
            <ul class="nav nav-list">
                <li class="nav-header">{{ $productBlock['title'] }}</li>
                @foreach($productBlock['products'] as $product)
                    <li><a href="/product/view?id={{ $product->productID }}">{{ $product->name }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
