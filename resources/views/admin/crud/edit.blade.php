@extends('layouts.admin')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading">
        <a href="/admin/{{ strtolower($modelName) }}">&larr; Return to list</a>
    </div>
    <!-- /.panel-heading -->
    <div class="panel-body">
        @php $formatter->renderForm(); @endphp
    </div>
    <!-- /.panel-body -->
</div>
@endsection
