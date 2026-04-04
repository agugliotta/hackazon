<script>
    $(function () {
        $(".remove_follower").click(function(){
            var block = $(this).parents('.collapsible-block');
            var follower_id = block.attr('data-id');
            $.ajax({
                url:'/wishlist/remove_follower', type:"POST",
                data: {follower_id: follower_id}, dataType:"json",
                success: function(data){ if (data.success) { block.remove(); } }
            });
        });
        $(".toggle").click(function(){
            $(this).parents('.collapsible-block').children(".block-content").slideToggle();
        });
        $("#wishlist_search_form").submit(function(){
            $.ajax({
                url:'/wishlist/search', type:"POST",
                data: $("#wishlist_search_form").serialize(), dataType:"json",
                success: function(data){
                    var output = '';
                    if (data.length == 0) {
                        output = '<div class="alert alert-danger text-center" style="margin-top: 10px;" role="alert"><h2>No results</h2></div>';
                    } else {
                        for (i in data) {
                            output = output + '<div class="panel panel-primary js-people-box" data-id="'+data[i].id+'"><div class="panel-heading">' + data[i].username + '</div>';
                            output = output + '<div class="panel-body"><ul style="list-style:none">';
                            var cntList = data[i].wishLists.length;
                            var c = 0;
                            for (j in data[i].wishLists) {
                                if (c < 3) {
                                    output = output + '<li><a href="/wishlist/view/' + data[i].wishLists[j].id + '">' + data[i].wishLists[j].name + '</a></li>';
                                }
                                c = c + 1;
                            }
                            if (cntList > 3) { output = output + '<li>Total: ' + cntList + ' lists</li>'; }
                            var remember = data[i].remembered
                                ? '<div class="remembered">Remembered</div>'
                                : '<button class="btn btn-primary remember" onclick="remember(this)">Remember</button>';
                            output = output + '</ul><div class="remember">'+remember+'</div></div></div>';
                        }
                    }
                    $('.product-list').empty().append(output);
                },
                fail: function(){ alert("error"); }
            });
            return false;
        });
    });

    function remember(el) {
        @if(!auth()->user())
            window.location.href = "/user/login?return_url={{ rawurlencode('/wishlist/') }}";
            return false;
        @endif
        var userId = $(el).parents('.js-people-box').attr('data-id');
        $.ajax({
            url:'/wishlist/remember', type:"POST",
            data: {user_id: userId}, dataType:"json",
            success: function(data){
                if (data.success) { $(el).parent('.remember').empty().append('<div class="remembered">Remembered!</div>'); }
            }
        });
    }
</script>

<form id="wishlist_search_form" role="search" class="form-inline navbar-form navbar-left search-form">
    <div class="form-group search-field-box">
        <input name="search" type="text" class="form-control search-field" placeholder="Type a person's name or email address"/>
    </div>
    <div class="form-group">
        <button class="btn btn-default" type="submit">Search</button>
    </div>
</form>
