@extends('layouts.admin')

@section('content')
<div class="panel panel-default product-page">
    <div class="panel-heading">
        <a href="/admin/{{ strtolower($modelName) }}">&larr; Return to list</a>
    </div>
    <!-- /.panel-heading -->
    <div class="panel-body">
        <div class="col-xs-6 col-md-6">
            @php $formatter->renderForm(); @endphp
        </div>
        <div class="col-xs-6 col-md-6">
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
                    @foreach($orderItems as $orderItem)
                        <tr>
                            <td class="product-image">
                                <div class="img-thumbnail-wrapper">
                                    <a href="/product/view?id={{ $orderItem->product->id() }}"><img src="/products_pictures/{{ $orderItem->product->picture }}" alt=""/></a>
                                </div>
                            </td>
                            <td><a href="/product/view?id={{ $orderItem->product_id }}">{{ $orderItem->name }}</a></td>
                            <td align="center">{{ $orderItem->qty }}</td>
                            <td align="right">${{ $orderItem->price * $orderItem->qty }}</td>
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
                    <td align="right" colspan="4"><strong>${!! $order->orderItems->getItemsTotal() !!}</strong></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
    <!-- /.panel-body -->
</div>
@endsection
