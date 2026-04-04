<script type="text/x-template" id="tplEditWishListForm">
    <form action="#" role="form" class="wish-list-edit-form">
        <div class="form-group">
            <label>Name</label>
            <input type="text" class="form-control js-name-field" placeholder="Enter Wish List Name" name="name" required>
        </div>
        <div class="form-group">
            <label>Type</label>
            <select class="form-control" name="type" required>
                <option value="{{ \App\Models\Wishlist::TYPE_PRIVATE }}" selected>Private</option>
                <option value="{{ \App\Models\Wishlist::TYPE_PUBLIC }}">Public</option>
            </select>
        </div>
        {!! $controller->renderTokenField('wishlist_add') !!}
        <input type="hidden" name="id"/>
    </form>
</script>
