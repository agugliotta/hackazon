@php
$lastItem = array_pop($breadcrumbs);
@endphp
<ol class="breadcrumb">
    @foreach($breadcrumbs as $key => $item)
        <li><a href="{!! $key !!}">{!! $item !!}</a></li>
    @endforeach
    <li class="active">{!! $lastItem !!}</li>
</ol>
