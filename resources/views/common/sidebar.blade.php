<!--<div class="col-md-3">-->
<div class="dropdown sidebar-menu-inside">
    <button class="btn btn-default hide" data-toggle="dropdown" id="sidebar-link">Shop By Department <b class="caret"></b></button>
    <ul class="dropdown-menu menu" role="menu" aria-labelledby="sidebar-link">
        @foreach($sidebar as $value)
            <li><a href="/category/view?id={!! $value->categoryID !!}">{!! $value->name !!}</a>
                @if(count($value->childs) > 0)
                    <ul>
                        @foreach($value->childs as $subcategory)
                            <li><a href="/category/view?id={!! $subcategory->categoryID !!}">{!! $subcategory->name !!}</a></li>
                        @endforeach
                    </ul>
                @endif
            </li>
        @endforeach
    </ul>
</div>
<!--</div>-->
