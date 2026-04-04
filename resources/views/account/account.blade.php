@extends('layouts.app')

@section('content')
<div class="container js-container account-page {{ $useRest ? 'js-disabled-hashchange' : '' }}">
    <div class="row">
        <div class="col-lg-12" id="header_block">
            <h1 class="page-header">My Account</h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li class="active">My Account</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            @if($useRest)
                <div class="js-account" id="account_block"></div>
            @else
                @if(session('success'))
                    <div class="alert alert-success" role="alert">{{ session('success') }}</div>
                @endif
                <ul id="myTab" class="nav nav-tabs" role="tablist">
                    <li class="active"><a href="#my-orders" data-toggle="tab">My Latest Orders</a></li>
                    <li><a href="#profile" data-toggle="tab">Profile</a></li>
                </ul>
                <div id="myTabContent" class="tab-content">
                    <div class="tab-pane fade in active latest-orders" id="my-orders">
                        @include('account._order_list')
                        <p class="text-right">
                            <a href="/account/orders" id="order_link" class="btn btn-primary ladda-button" data-style="expand-right"><span class="ladda-label">Go to my orders</span></a>
                        </p>
                    </div>
                    <div class="tab-pane fade profile-show" id="profile">
                        @include('account._profile_info')
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($useRest)
<script type="text/javascript">
    var orderStatusLabelMapping = {!! json_encode($orderStatusLabelMapping ?? []) !!};

    function order_status(status) {
        var canonicalStatus = $.trim(status).toLowerCase(),
            label = orderStatusLabelMapping[canonicalStatus]
                ? orderStatusLabelMapping[canonicalStatus] : 'label-default';
        return $('<span class="label ' + label + '"></span>').text(status)[0].outerHTML;
    }
</script>

<script type="text/javascript" charset="utf-8" src="/js/can.custom.min.js"></script>
<script type="text/javascript" charset="utf-8" src="/js/jquery.form.min.js"></script>
<script type="text/javascript" charset="utf-8" src="/js/XMLWriter.js"></script>
<script type="text/javascript" charset="utf-8" src="/js/account.js"></script>

<script type="text/x-handlebars" charset="utf-8" id="layout_header">
    <h1 class="page-header">{{ "{{ title }}" }}</h1>
    <ol class="breadcrumb">
        {{"{{"}}#each breadcrumbs {{"}}"}}
            <li {{"{{"}}#if active {{"}}"}}class="active"{{"{{"}}/ if{{"}}"}}>
                {{"{{"}}#if url{{"}}"}}
                    <a href="{{ "{{ url }}" }}">{{ "{{ name }}" }}</a>
                {{"{{"}}else{{"}}"}}
                    {{ "{{ name }}" }}
                {{"{{"}}/ if{{"}}"}}
            </li>
        {{"{{"}}/ each{{"}}"}}
    </ol>
</script>

<script type="text/x-handlebars" id="layout_account">
    <ul id="myTab" class="nav nav-tabs" role="tablist">
        <li data-id="my-orders"><a href="#!">My Latest Orders</a></li>
        <li data-id="profile"><a href="#!profile">Profile</a></li>
    </ul>
    <div id="myTabContent" class="tab-content">
        <div class="tab-pane latest-orders" id="my-orders">
            <div class="js-order-list"></div>
            <p class="text-right">
                <a href="/account#!orders" id="order_link" class="btn btn-primary ladda-button"
                    data-style="expand-right"><span class="ladda-label">Go to my orders</span></a>
            </p>
        </div>
        <div class="tab-pane profile-show js-profile" id="profile">
            {{"{{"}}> tpl_user_profile{{"}}"}}
        </div>
    </div>
</script>

<script type="text/x-handlebars" charset="utf-8" id="tpl_order_list">
    <div class="row">
        <div class="col-xs-12">
            {{"{{"}}#if orders.length{{"}}"}}
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Order №</th>
                        <th>Date</th>
                        <th>Payment Method</th>
                        <th>Shipping Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    {{"{{"}}#each orders{{"}}"}}
                    <tr>
                        <td><a href="/account#!orders/{{"{{"}} increment_id {{"}}"}}">{{"{{"}} increment_id {{"}}"}}</a></td>
                        <td>{{"{{"}}formatDate created_at{{"}}"}}</td>
                        <td>{{"{{"}} payment_method {{"}}"}}</td>
                        <td>{{"{{"}} shipping_method {{"}}"}}</td>
                        <td>{{"{{"}}order_status status{{"}}"}}</td>
                    </tr>
                    {{"{{"}}/ each{{"}}"}}
                </tbody>
            </table>
            {{"{{"}}/ if{{"}}"}}
            {{"{{"}}#if paging{{"}}"}}
                {{"{{"}}pager orders{{"}}"}}
            {{"{{"}}/ if{{"}}"}}
        </div>
    </div>
</script>

<script type="text/x-handlebars" charset="utf-8" id="tpl_user_profile">
    <div class="row">
        <div class="col-xs-8">
            <table class="table profile-table table-striped">
                <thead><tr><td></td></tr></thead>
                <tbody>
                <tr><td>Username:</td><td>{{"{{"}} user.username {{"}}"}}</td></tr>
                <tr><td>E-mail:</td><td>{{"{{"}} user.email {{"}}"}}</td></tr>
                <tr><td>First Name:</td><td>{{"{{"}} user.first_name {{"}}"}}</td></tr>
                <tr><td>Last Name:</td><td>{{"{{"}} user.last_name {{"}}"}}</td></tr>
                <tr><td>Phone:</td><td>{{"{{"}} user.user_phone {{"}}"}}</td></tr>
                </tbody>
            </table>
        </div>
        <div class="col-xs-4">
            {{"{{"}}#if user.photoUrl{{"}}"}}
                <img src="{{"{{"}} baseImgPath {{"}}"}}{{"{{"}} user.photoUrl{{"}}"}}" alt="" class="profile-picture img-responsive img-bordered img-thumbnail" />
            {{"{{"}}else{{"}}"}}
                {{"{{"}}#if user.photo{{"}}"}}
                    <img src="{{"{{"}} baseImgPath {{"}}"}}{{"{{"}} user.photo{{"}}"}}" alt="" class="profile-picture img-responsive img-bordered img-thumbnail" />
                {{"{{"}}/ if{{"}}"}}
            {{"{{"}}/ if{{"}}"}}
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <p class="text-right buttons-row">
                <a href="/account#!profile/edit" id="profile_link" class="btn btn-primary">Edit Profile</a>
            </p>
        </div>
    </div>
</script>
@else
<script>
    $(function() {
        Ladda.bind('#order_link');
        $('#order_link').on('click', function(e) {
            var l = Ladda.create(document.querySelector('#order_link'));
            l.start();
            window.location.href = "/account/orders";
            return false;
        });
    });
</script>
@endif
@endsection
