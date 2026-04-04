@extends('layouts.admin')

@section('content')
<div class="panel panel-default">
    <div class="panel-heading">
        {!! ucfirst($modelName) !!} list

        <div class="pull-right">
            <div class="btn-group">
                <button data-toggle="dropdown" class="btn btn-default btn-xs dropdown-toggle" type="button">
                    Actions
                    <span class="caret"></span>
                </button>
                <ul role="menu" class="dropdown-menu pull-right">
                    <li><a href="/admin/{{ strtolower($modelName) }}/new">Add new {!! $modelName !!}</a></li>
                </ul>
            </div>
        </div>
    </div>
    <!-- /.panel-heading -->
    <div class="panel-body">
        <div class="table-responsive">
            <table id="itemList" class="table table-striped table-bordered table-hover dataTable no-footer">
                <thead>
                    <tr role="row">
                        @foreach($listFields as $field => $data)
                            <th rowspan="1" style="{{ !empty($data['width']) ? 'width: '.$data['width'].'px;' : '' }}">{!! $data['title'] ?? ucfirst($field) !!}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
        <!-- /.table-responsive -->
    </div>
    <!-- /.panel-body -->
</div>

@php
$columns = [];
foreach ($listFields as $field => $data) {
    $columns[] = [
        'className' => $data['column_classes'] ?? '',
        'data'      => $field,
        'dataSrc'   => 'data',
        'orderable' => $data['orderable'] ?? true,
        'searching' => $data['searching'] ?? true,
    ];
}
@endphp

<script type="text/javascript">
    jQuery(function () {
        $('#itemList').dataTable({
            ajax: '/admin/{{ $modelName }}/',
            serverSide: true,
            pageLength: 25,
            columns: JSON.parse('{!! addslashes(json_encode($columns)) !!}')
        });
    });
</script>
@endsection
