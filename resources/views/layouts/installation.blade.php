<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hackazon vulnerable website">
    <meta name="author" content="">

    <title>Hackazon Installation{{ isset($pageTitle) ? ' &mdash; ' . $pageTitle : '' }}</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/bootstrapValidator.css" rel="stylesheet">
    <link href="/css/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="/css/sb-admin-2.css" rel="stylesheet">
    <link href="/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    <!--[if lt IE 9]>
    <script src="/js/html5shiv.js"></script>
    <script src="/js/respond-1.4.2.min.js"></script>
    <![endif]-->

    <script src="/js/jquery-1.10.2.js"></script>
    <script src="/js/jquery-migrate-1.2.1.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/bootstrapValidator.min.js"></script>
    <script src="/js/tools.js"></script>
</head>

<body>
<div id="container" class="installation">
    <div class="container">
        <div class="row">
            <h1 class="text-center">Hackazon Installation Wizard</h1>
        </div>
    </div>

    @if(isset($steps))
        <div class="container">
            <div class="row step-meter">
                <div class="row bs-wizard" style="border-bottom:0;">
                    @foreach($steps as $stepName => $stepData)
                        <div class="col-xs-{{ count($steps) == 3 ? 4 : 3 }} bs-wizard-step {{ $stepData['started'] ? 'complete' : 'disabled' }}{{ $stepData['is_last_started'] ? ' active' : '' }}">
                            <div class="text-center bs-wizard-stepnum {{ $stepData['current'] ? 'active' : '' }}">
                                {!! $stepData['title'] !!}
                            </div>
                            <div class="progress"><div class="progress-bar"></div></div>
                            <a href="/install/{{ $stepName }}" class="bs-wizard-dot"></a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    <div class="container">
        <div class="col-md-6 col-md-offset-3">
            @if(isset($steps))
                <h3>{!! $step->getTitle() !!}</h3>
                @if(isset($errorMessage) && !empty($errorMessage))
                    <div class="alert alert-danger">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                        <strong>{!! $errorMessage !!}</strong>
                    </div>
                @endif
            @endif

            @yield('content')
        </div>
        <div class="col-md-6 col-md-offset-3" style="text-align: center;">
            <br/><br/><br/><br/>
            <a href="/install?force=1">Restart installation</a>
        </div>
    </div>
</div>

</body>
</html>
