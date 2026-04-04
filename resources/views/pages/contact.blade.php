@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">Contact <small>We'd Love to Hear From You!</small></h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li class="active">Contact</li>
            </ol>
        </div>
        <div class="col-lg-12">
            <iframe width="100%" height="400px" frameborder="0" scrolling="no" marginheight="0" marginwidth="0" src="http://maps.google.com/maps?hl=en&amp;ie=UTF8&amp;ll=37.0625,-95.677068&amp;spn=56.506174,79.013672&amp;t=m&amp;z=4&amp;output=embed"></iframe>
        </div>
    </div>
    <div class="row">
        <div class="col-sm-8">
            <h3>Let's Get In Touch!</h3>
            <p>Lid est laborum dolo rumes fugats untras. Etharums ser quidem rerum facilis dolores nemis omnis fugats vitaes nemo minima rerums unsers sadips amets.</p>
            <div style="display: none" class="alert alert-success"></div>
            <form role="form" method="POST" id="contactForm" class="form-horizontal hw-form-contact">
                <div class="row form-group col-lg-4 field-group pull-left r-margin">
                    <input type="text" maxlength="100" required class="form-control" placeholder="Username" name="contact_name" id="userName">
                </div>
                <div class="form-group col-lg-4 field-group pull-left r-margin">
                    <input type="email" name="contact_email" required class="form-control" id="input2" data-validation="email" placeholder="Email Address">
                </div>
                <div class="form-group col-lg-4 field-group pull-left">
                    <input type="phone" name="contact_phone" required class="form-control" id="input3" placeholder="Phone Number">
                </div>
                <div class="clearfix"></div>
                <div class="form-group col-lg-12 field-group">
                    <textarea name="contact_message" required class="form-control" rows="6" id="input4" placeholder="Message"></textarea>
                </div>
                <div class="form-group col-lg-12 field-group">
                    <input type="hidden" name="save" value="contact">
                    <button id="form-submit" type="submit" class="btn btn-primary ladda-button" data-style="expand-right"><span class="ladda-label">Submit</span></button>
                </div>
            </form>
            <div class="repeat-contact js-repeat-contact">
                <button class="btn btn-primary js-repeat-contact-link">Send One More Request</button>
            </div>
        </div>
        <div class="col-sm-4">
            <h3>Modern Business</h3>
            <h4>Hackazon</h4>
            <p>5555 44th Street N.<br>Bootstrapville, CA 32323<br></p>
            <p><i class="fa fa-phone"></i> <abbr title="Phone">P</abbr>: (555) 984-3600</p>
            <p><i class="fa fa-envelope-o"></i> <abbr title="Email">E</abbr>: <a href="mailto:feedback@hackazon.webscantest.com">feedback@hackazon.webscantest.com</a></p>
            <p><i class="fa fa-clock-o"></i> <abbr title="Hours">H</abbr>: Monday - Friday: 9:00 AM to 5:00 PM</p>
        </div>
    </div>
</div>

<script>
    $(function() {
        Ladda.bind('input[type=submit]');
        var form = $('#contactForm'),
            repeatContactBlock = $('.js-repeat-contact');

        form.hzBootstrapValidator().on('success.form.bv', function(e) {
            var l = Ladda.create(document.querySelector('#form-submit'));
            l.start();
            var data = {};
            $("#contactForm").serializeArray().map(function(x){ data[x.name] = x.value; });
            $.ajax({
                url: '/contact', type: "POST", dataType: "json",
                data: "data=" + JSON.stringify(data),
                success: function(data) {
                    $(".alert").empty().append('Thank you for your question. We will contact you as soon.').show();
                    form.hide();
                    repeatContactBlock.show();
                },
                fail: function() { $(".alert").empty().append('There is some error happened during processing your request.').show(); }
            }).always(function() { l.stop(); });
            return false;
        });
        $(document).on('click', '.js-repeat-contact-link', function () { location.reload(); });
    });
</script>
@endsection
