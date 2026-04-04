@php
$productRating = is_array($product) ? $product['customers_rating'] : $product->customers_rating;
@endphp
@for($i = 1; $i < 6; $i++)
    @if($i > $productRating)
        <span class="glyphicon glyphicon-star-empty"></span>
    @else
        <span class="glyphicon glyphicon-star"></span>
    @endif
@endfor
