@extends('layouts.app')

@section('content')
<script>
    $(function () {
        $("#place_order").click(function(){
            var el = $(this), l = el.ladda();
            el.attr('disabled', 'disabled'); l.ladda('start');
            $.ajax({
                url:'/checkout/placeOrder',
                data: { _csrf_checkout_step4: el.data('token') },
                type:"POST", dataType:"json",
                success: function(data) {
                    if (data.location) { window.location.href = data.location; }
                    else if (data.success) { window.location.href = "/checkout/order"; }
                    else { alert("error"); }
                },
                fail: function(){ l.ladda('start'); el.removeAttr('disabled'); alert("error"); }
            });
        });
    });
</script>

@include('cart.cart_header')
<div class="tab-pane active checkout-page" id="step4">
    <div class="row">
        <div class="col-xs-12 col-sm-4">
            <div class="well bg-info">
                <table class="table">
                    <thead>
                    <tr><th>Personal info</th></tr>
                    <tr>
                        <td>
                            <div class="blockShadow bg-info">
                                <h3>Shipping Address</h3>
                                <b>{!! $shippingAddress->getWrapperOrValue('full_name') !!}</b><br />
                                {!! $shippingAddress->getWrapperOrValue('address_line_1') !!}<br />
                                {!! $shippingAddress->getWrapperOrValue('address_line_2') !!}<br />
                                {!! $shippingAddress->getWrapperOrValue('city') !!} {!! $shippingAddress->getWrapperOrValue('region') !!} {!! $shippingAddress->getWrapperOrValue('zip') !!}<br />
                                {!! $shippingAddress->getWrapperOrValue('country_id') !!}<br />
                                {!! $shippingAddress->getWrapperOrValue('phone') !!}<br />
                            </div>
                            <div class="blockShadow bg-info">
                                <h3>Billing Address</h3>
                                <b>{!! $billingAddress->getWrapperOrValue('full_name') !!}</b><br />
                                {!! $billingAddress->getWrapperOrValue('address_line_1') !!}<br />
                                {!! $billingAddress->getWrapperOrValue('address_line_2') !!}<br />
                                {!! $billingAddress->getWrapperOrValue('city') !!} {!! $billingAddress->getWrapperOrValue('region') !!} {!! $billingAddress->getWrapperOrValue('zip') !!}<br />
                                {!! $billingAddress->getWrapperOrValue('country_id') !!}<br />
                                {!! $billingAddress->getWrapperOrValue('phone') !!}<br />
                            </div>
                        </td>
                    </tr>
                    </thead>
                </table>
            </div>
        </div>
        <div class="col-xs-12 col-sm-8">
            @include('home.product_list', ['productListData' => ['products' => $items ?? []]])
            <div class="row">
                <div class="col-xs-6"></div>
                <div class="col-xs-6">
                    <button id="place_order" data-token="{{ $controller->getToken('checkout_step4') }}"
                            class="btn btn-primary pull-right ladda-button" data-style="expand-left">
                        <span class="ladda-label">Place Order <span class="glyphicon glyphicon-chevron-right icon-white"></span></span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
@include('cart.cart_footer')
@endsection
