@if(isset($categories) && !is_null($categories))
    <div class="navbar" role="navigation">
        <div class="container">
            <!-- Collect the nav links, forms, and other content for toggling -->
            <div class="collapse navbar-collapse navbar-ex1-collapse">
                <ul class="nav navbar-nav">
                    @foreach($categories as $value)
                        <li><a href="/product/category/{!! $value->categoryID !!}">{!! $value->name !!}</a></li>
                    @endforeach
                </ul>
            </div>
            <!-- /.navbar-collapse -->
        </div>
        <!-- /.container -->
    </div>
@endif
