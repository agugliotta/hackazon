@foreach($breadcrumbs as $category)
    @php $lastItem = array_pop($category); @endphp
    <ol class="breadcrumb">
        @foreach($category as $key => $item)
            <li><a href="{!! $key !!}">{!! $item !!}</a></li>
        @endforeach
        <li class="active">{!! $lastItem !!}</li>
    </ol>
@endforeach
