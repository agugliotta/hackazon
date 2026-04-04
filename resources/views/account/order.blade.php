@extends('layouts.app')

@php
function orderStatusLabel($status) {
    $map = ['shipped'=>'label-info','canceled'=>'label-danger','complete'=>'label-success','pending'=>'label-warning','processing'=>'label-primary'];
    $key = strtolower(trim($status));
    $cls = $map[$key] ?? 'label-default';
    return '<span class="label '.$cls.'">'.htmlspecialchars($status).'</span>';
}
@endphp

@section('content')
<div class="container order-page">
    <div class="row">
        <div class="col-md-12 col-sm-12">
            <h1 class="page-header">Order №{{ $order->increment_id }} <small>{!! orderStatusLabel($order->status) !!}</small></h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li><a href="/account{{ $useRest ?? false ? '#!' : '#my-orders' }}">My Account</a></li>
                <li><a href="/account{{ $useRest ?? false ? '#!orders' : '/orders' }}">Orders</a></li>
                <li class="active">Order №{{ $order->increment_id }}</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-xs-12">
            <div class="panel panel-success">
                <div class="panel-heading">
                    <h3 class="panel-title">Overview</h3>
                </div>
                <div class="panel-body">
                    <dl class="dl-horizontal">
                        <dt>Date</dt>
                        <dd>{{ date('m/d/Y', strtotime($order->created_at)) }}</dd>
                        <dt>Status</dt>
                        <dd>{!! orderStatusLabel($order->status) !!}</dd>
                        @if($order->discount > 0)
                            <dt>Discount</dt>
                            <dd>{!! $order->discount !!}%</dd>
                        @endif
                        <dt>Total</dt>
                        <dd><span class="label label-danger">${{ $order->orderItems->sum(function($i){ return $i->price * $i->qty; }) }}</span></dd>
                    </dl>
                </div>
            </div>

            <table class="table table-striped">
                <thead>
                    <tr>
                        <th colspan="2">Items</th>
                        <th width="50">count</th>
                        <th width="70">total</th>
                    </tr>
                </thead>
                <tbody>
                    @if($orderItems)
                        @foreach($orderItems as $item)
                            <tr>
                                <td class="product-image">
                                    <div class="img-thumbnail-wrapper">
                                        <a href="/product/view?id={{ $item->product_id }}"><img src="/products_pictures/{!! $item->product->picture ?? '' !!}" alt=""/></a>
                                    </div>
                                </td>
                                <td><a href="/product/view?id={{ $item->product_id }}">{{ $item->name }}</a></td>
                                <td align="center">{{ $item->qty }}</td>
                                <td align="right">${{ $item->price * $item->qty }}</td>
                            </tr>
                        @endforeach
                    @endif
                    <tr>
                        <th colspan="4">Services</th>
                    </tr>
                    <tr class="info">
                        <td colspan="2">Shipping: {{ $order->shipping_method }}</td>
                        <td align="right" colspan="2">$0</td>
                    </tr>
                    <tr class="info">
                        <td colspan="2">Payment: {{ $order->payment_method }}</td>
                        <td align="right" colspan="2">$0</td>
                    </tr>
                    <tr class="danger">
                        <td align="right" colspan="4"><strong>${{ $orderItems ? collect($orderItems)->sum(function($i){ return $i->price * $i->qty; }) : 0 }}</strong></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
