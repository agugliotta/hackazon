@php
$iconVariants = ['refresh','download','pencil','camera','qrcode','tags','random'];
$reviewColumns = isset($reviewColumns) && is_numeric($reviewColumns) ? $reviewColumns : 2;
$columnStyles = $reviewColumns == 3
    ? 'col-xs-12 col-sm-12 col-md-4 col-md-4'
    : 'col-xs-12 col-sm-12 col-md-6 col-md-6';
@endphp
<div class="row">
    @foreach($selectedReviews as $review)
        @php $iconKey = array_rand($iconVariants); @endphp
        <div class="{{ $columnStyles }}">
            <div class="article">
                <article>
                    @if(!isset($showReviewIcons) || $showReviewIcons !== false)
                        <div class="review-icon-box"><span class="review-icon glyphicon glyphicon-{{ $iconVariants[$iconKey] }}"></span></div>
                    @endif
                    <h4>{{ $review->username }}</h4>
                    <h5>about <a href="/product/view?id={{ $review->product->productID }}">{{ $review->product->name }}</a></h5>
                    <p>{{ mb_substr($review->review, 0, 300, 'utf-8') }} <a href="/product/view?id={{ $review->product->productID }}">More <span class="glyphicon glyphicon-chevron-right"></span></a></p>
                </article>
            </div>
        </div>
    @endforeach
</div>
