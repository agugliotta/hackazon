@php
$categoryColumns = [];
$i = 0;
foreach ($sidebar as $footerCat) {
    $categoryColumns[$i % 6][] = $footerCat;
    $i++;
}
@endphp
<div class="container">
    <div class="footer">
        <div class="row hidden-print">
            @foreach($categoryColumns as $i => $columnCategories)
                <div class="col-xs-3 col-sm-2 col-md-2 col-lg-2">
                    @foreach($columnCategories as $j => $category)
                        <ul class="unstyled">
                            <li class="footer-title"><a href="/category/view?id={{ $category->categoryID }}">{{ $category->name }}</a></li>
                        </ul>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
</div>
