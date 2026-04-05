@php
function orderStatusLabel($status) {
    $map = [
        'shipped'    => 'label-info',
        'canceled'   => 'label-danger',
        'complete'   => 'label-success',
        'pending'    => 'label-warning',
        'processing' => 'label-primary',
    ];
    $key = strtolower(trim($status));
    $cls = $map[$key] ?? 'label-default';
    return '<span class="label ' . $cls . '">' . htmlspecialchars($status) . '</span>';
}
@endphp

@php $isPaginator = $myOrders instanceof \Illuminate\Pagination\LengthAwarePaginator; @endphp

@if(count($myOrders) == 0)
    @if(!$isPaginator || $myOrders->total() == 0)
        <h2>You don't have any orders.</h2>
    @else
        <h2>Incorrect page.</h2>
    @endif
@endif

<div class="row">
    <div class="col-xs-12">
        @if(count($myOrders) > 0)
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
            @foreach($myOrders as $order)
            <tr>
                <td><a href="/account/orders/{!! $order->increment_id !!}">{!! $order->increment_id !!}</a></td>
                <td>{{ date('m/d/Y', strtotime($order->created_at)) }}</td>
                <td>{!! $order->payment_method !!}</td>
                <td>{!! $order->shipping_method !!}</td>
                <td>{!! orderStatusLabel($order->status) !!}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
        @endif

        @if($isPaginator)
            {!! $myOrders->links() !!}
        @endif
    </div>
</div>
