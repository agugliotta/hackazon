@extends('layouts.app')

@section('content')
<div class="container profile-edit">
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <h1 class="page-header">Edit Profile</h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li><a href="/account{{ ($useRest ?? false) ? '#!profile/edit' : '#profile' }}">My Account</a></li>
                <li class="active">Edit Profile</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            @if(!empty($success))
                <div class="alert alert-success" role="alert">{!! $success !!}</div>
            @endif
            @if(!empty($errorMessage))
                <div class="alert alert-danger" role="alert">{!! $errorMessage !!}</div>
            @endif
            @include('account._profile_form')
        </div>
    </div>
</div>
@endsection
