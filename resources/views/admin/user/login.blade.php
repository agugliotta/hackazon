@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-4 col-md-offset-4">
            <div class="login-panel panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">Please Sign In</h3>
                </div>
                <div class="panel-body">
                    @if(isset($errorMessage) && !empty($errorMessage))
                        <div class="alert alert-danger alert-dismissable">
                            <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                            <strong>{!! $errorMessage !!}</strong>
                        </div>
                    @endif

                    <form role="form" method="post" action="/admin/user/login{{ isset($returnUrl) && $returnUrl ? '?return_url=' . rawurlencode($returnUrl) : '' }}">
                        <fieldset>
                            <div class="form-group">
                                <input class="form-control" placeholder="Username/E-mail" name="username" type="text" autofocus>
                            </div>
                            <div class="form-group">
                                <input class="form-control" placeholder="Password" name="password" type="password" value="">
                            </div>
                            <button type="submit" class="btn btn-lg btn-success btn-block">Login</button>
                        </fieldset>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
