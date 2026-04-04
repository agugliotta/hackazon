<div class="col-lg-12 text-center">
    <div class="container section-title">
        <h2>{{ $sectionData['title'] }}</h2>
    </div>
    <hr>
</div>

<div class="home-product-sections">
@php $productListData = $sectionData; @endphp
@include('home.product_list')
</div>

@if(isset($sectionData['reviews']) && is_array($sectionData['reviews']) && count($sectionData['reviews']))
    @php $selectedReviews = $sectionData['reviews']; $reviewColumns = 3; $showReviewIcons = false; @endphp
    <div class="container"><div class="col-xs-12">
        @include('home.top_reviews')
    </div></div>
@endif
