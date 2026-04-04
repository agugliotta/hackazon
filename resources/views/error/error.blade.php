@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">{!! $pageTitle ?? 'Error' !!}</h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li class="active">Error</li>
            </ol>
        </div>
    </div>
    <div class="row error-page">
        <div class="col-lg-12">
            <p>Please try to change your request.</p>
        </div>
    </div>
</div>
@endsection
