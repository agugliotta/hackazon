<ul class="thumbnails product-list-inline-small">
    @foreach($special_offers as $so)
        @php $offer = $so->product_offers; @endphp
        <li class="col-xs-3">
            <div class="thumbnail">
                <div class="special-offer-big-img">
                    <a class="img-wrap" href="/product/view?id={{ $offer->productID }}"><img class="img-responsive" src="/products_pictures/{{ $offer->picture }}" alt=""></a>
                </div>
                <div class="caption">
                    <a href="/product/view?id={{ $offer->productID }}" title="{{ $offer->name }}">{{ \Illuminate\Support\Str::limit($offer->name, 50, '') }}</a>
                    <p>{{ \Illuminate\Support\Str::limit($offer->description, 80, '') }}<span class="label label-info pull-right price">${{ $offer->Price }}</span></p>
                </div>
            </div>
        </li>
    @endforeach
</ul>
