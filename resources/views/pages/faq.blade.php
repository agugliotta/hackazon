@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1>Frequently Asked Questions</h1>
            <ol class="breadcrumb">
                <li><a href="/">Home</a></li>
                <li class="active">Frequently Asked Questions</li>
            </ol>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-12">
            @if(session('success'))
                <div class="alert alert-success" role="alert">{{ session('success') }}</div>
            @endif
            @if(isset($entries) && !is_null($entries))
                <div class="panel-group" id="accordion">
                    @foreach($entries as $obj)
                        <div class="panel panel-default">
                            <div class="panel-heading">
                                <h4 class="panel-title">
                                    <a class="accordion-toggle" data-toggle="collapse" data-parent="#accordion" href="#collapse{{ $obj->faqID }}">
                                        {!! $obj->question !!}
                                    </a>
                                </h4>
                            </div>
                            <div id="collapse{{ $obj->faqID }}" class="panel-collapse collapse">
                                <div class="panel-body">
                                    {!! !empty($obj->answer) ? $obj->answer : 'Not answered yet.' !!}
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    <div class="section">
        <form role="form" method="post" action="/faq" id="faqForm">
            <div class="form-group">
                <label for="userEmail">Email address</label>
                <input type="email" class="form-control" name="userEmail" id="userEmail" placeholder="Enter email" required data-validation="email">
            </div>
            <div class="form-group">
                <label for="userQuestion">Question</label>
                <textarea class="form-control" name="userQuestion" id="userQuestion" placeholder="Type your question here..." required></textarea>
            </div>
            {!! $controller->renderTokenField('faq') !!}
            <button id="form-submit" type="submit" class="btn btn-primary ladda-button" data-style="expand-right"><span class="ladda-label">Submit</span></button>
        </form>
    </div>
</div>

<script>
    $(function() {
        $('#faqForm').on('submit', function(e) {
            e.preventDefault();
            var l = Ladda.create(document.querySelector('#form-submit'));
            l.start();
            $.ajax({
                url: '/faq', type: "POST", dataType: "json", data: $(this).serialize(),
                success: function(data){ location.reload(); },
                error: function(){ $(".alert").empty().append('There is some error happened during processing your request.').show(); }
            }).always(function() { l.stop(); });
        });
    });
</script>
@endsection
