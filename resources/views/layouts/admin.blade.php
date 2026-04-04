<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Hackazon vulnerable website">
    <meta name="author" content="">

    <title>Webscantest Admin{{ isset($pageTitle) ? ' &mdash; ' . $pageTitle : '' }}</title>

    <link href="/css/bootstrap.min.css" rel="stylesheet">
    <link href="/css/bootstrapValidator.css" rel="stylesheet">
    <link href="/css/plugins/metisMenu/metisMenu.min.css" rel="stylesheet">
    <link href="/css/plugins/timeline.css" rel="stylesheet">
    <link href="/css/sb-admin-2.css" rel="stylesheet">
    <link href="/css/plugins/morris.css" rel="stylesheet">
    <link href="/font-awesome/css/font-awesome.min.css" rel="stylesheet" type="text/css">

    @if(isset($headCSS)){!! $headCSS !!}@endif

    <!--[if lt IE 9]>
    <script src="/js/html5shiv.js"></script>
    <script src="/js/respond-1.4.2.min.js"></script>
    <![endif]-->

    <script src="/js/jquery-1.10.2.js"></script>
    <script src="/js/jquery-migrate-1.2.1.js"></script>
    <script src="/js/bootstrap.min.js"></script>
    <script src="/js/bootstrapValidator.min.js"></script>
    <script src="/js/plugins/metisMenu/metisMenu.min.js"></script>
    <script src="/js/plugins/dataTables/jquery.dataTables.js"></script>
    <script src="/js/plugins/dataTables/dataTables.bootstrap.js"></script>
    <script src="/js/bootstrap.file-input.js"></script>
    <script src="/js/tools.js"></script>
    <script src="/js/sb-admin-2.js"></script>

    @if(isset($headScripts)){!! $headScripts !!}@endif
</head>

<body>

<div id="wrapper" class="admin">

<!-- Navigation -->
<nav class="navbar navbar-default navbar-static-top" role="navigation" style="margin-bottom: 0">
<div class="navbar-header">
    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-collapse">
        <span class="sr-only">Toggle navigation</span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
    </button>
    <a class="navbar-brand" href="/admin/">Hackazon Admin</a>
</div>

<ul class="nav navbar-top-links navbar-right">
    <li class="dropdown">
        <a class="dropdown-toggle" data-toggle="dropdown" href="#">
            <i class="fa fa-user fa-fw"></i> <i class="fa fa-caret-down"></i>
        </a>
        <ul class="dropdown-menu dropdown-user">
            <li><a href="#"><i class="fa fa-user fa-fw"></i> User Profile</a></li>
            <li><a href="#"><i class="fa fa-gear fa-fw"></i> Settings</a></li>
            <li class="divider"></li>
            <li><a href="/admin/user/logout"><i class="fa fa-sign-out fa-fw"></i> Logout</a></li>
        </ul>
    </li>
</ul>

<div class="navbar-default sidebar" role="navigation">
    <div class="sidebar-nav navbar-collapse">
        <ul class="nav" id="side-menu">
            @if(isset($sidebarLinks))
            @php $baseLen = strlen('/admin'); @endphp
            @foreach($sidebarLinks as $sbLink => $sbLinkData)
                @php $isLinkActive = (strlen($sbLink) <= $baseLen && request()->getRequestUri() == $sbLink)
                    || (strlen($sbLink) > $baseLen && strpos(request()->getRequestUri(), $sbLink) === 0); @endphp
            <li>
                <a href="{{ $sbLink }}" class="{{ $isLinkActive ? 'active' : '' }}"
                    ><i class="{!! $sbLinkData['link_class'] !!}"></i>{!! $sbLinkData['label'] !!}</a>
            </li>
            @endforeach
            @endif
        </ul>
    </div>
</div>
</nav>

<div id="page-wrapper">
    @if(isset($pageHeader))
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header">{!! $pageHeader !!}</h1>
        </div>
    </div>
    @endif

    @yield('content')

</div>
<!-- /#page-wrapper -->

</div>
<!-- /#wrapper -->

</body>
</html>
