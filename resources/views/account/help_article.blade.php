@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Help</h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li><a href="/account">Account</a></li>
                <li><a href="/account/help_articles">Help Articles</a></li>
                <li class="active">{!! $pageTitle !!}</li>
            </ol>
        </div>
        <div class="col-lg-12">
            {!! $pageContent !!}
        </div>
    </div>
</div>
@endsection
