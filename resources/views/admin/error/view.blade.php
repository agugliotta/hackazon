@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-md-6 col-md-offset-3">
            <div class="login-panel panel panel-default">
                <div class="panel-heading">
                    <h3 class="panel-title">{!! $pageTitle ?? 'Error' !!}</h3>
                </div>
                <div class="panel-body">
                    <p>Please try to change your request.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
