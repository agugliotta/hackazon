@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Help</h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li><a href="/account">Account</a></li>
                <li class="active">Help</li>
            </ol>
        </div>
        <div class="col-lg-6">
            <h3>Help Articles List</h3>
            <div class="list-group">
                @foreach($files as $file => $fileName)
                <a class="list-group-item" href="/account/help-articles?page={!! $fileName !!}"><span class="glyphicon glyphicon-file"></span> {!! ucwords($file) !!}</a>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
