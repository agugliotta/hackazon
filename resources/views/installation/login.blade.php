@extends('layouts.installation')

@section('content')
<br/><br/><br/>
<form action="/install/login" id="installLoginForm" method="POST">
    @if(isset($errors) && $errors)
        <div class="panel panel-danger">
            <div class="panel-body">
                {!! $errors !!}
            </div>
        </div>
    @endif
    <div class="form-group">
        <label for="password_field">Please enter an installer password:</label>
        <input class="form-control" type="password" name="password" value="" placeholder="Password" id="password_field" required/><br>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary pull-right">Log In</button>
    </div>
</form>

<script>
    $(function() {
        jQuery(function($) {
            var $settingsForm = $('#installLoginForm');
            $settingsForm.hzBootstrapValidator({});
        });
    });
</script>
@endsection
