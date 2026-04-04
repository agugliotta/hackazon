@extends('layouts.installation')

@section('content')
<p>Please check all parameters.</p>

<h4>Database:</h4>
<table class="table table-striped">
    <tbody>
    <tr><td>Host:</td><td>{!! $database['host'] !!}</td></tr>
    <tr><td>Port:</td><td>{!! $database['port'] !!}</td></tr>
    <tr><td>User:</td><td>{!! $database['user'] !!}</td></tr>
    <tr><td>Password:</td><td>***************</td></tr>
    <tr><td>Database:</td><td>{!! $database['db'] !!}</td></tr>
    </tbody>
</table>

<h4>Email:</h4>
<table class="table table-striped">
    <tbody>
    <tr><td>Type</td><td>{!! $email['type'] !!}</td></tr>
    @if($email['type'] == 'native')
        <tr><td>Mail Parameters</td><td>{!! $email['mail_parameters'] !!}</td></tr>
    @elseif($email['type'] == 'sendmail')
        <tr><td>Sendmail Command</td><td>{!! $email['sendmail_command'] !!}</td></tr>
    @elseif($email['type'] == 'smtp')
        <tr><td>Hostname</td><td>{!! $email['hostname'] !!}</td></tr>
        <tr><td>Port</td><td>{!! $email['port'] !!}</td></tr>
        <tr><td>Username</td><td>{!! $email['username'] !!}</td></tr>
        <tr><td>Password</td><td>***************</td></tr>
        <tr><td>Encryption</td><td>{!! $email['encryption'] !!}</td></tr>
        <tr><td>Timeout</td><td>{!! $email['timeout'] !!}</td></tr>
    @endif
    </tbody>
</table>

@if(isset($configsToAdd) && count($configsToAdd))
    <div class="alert alert-info">Hackazon can't create all necessary files, so you have to create the following
        files manually and copy indicated content inside of it.
    </div>
    @php $counter = 0; @endphp
    @foreach($configsToAdd as $fileName => $configContent)
        <div>
            <h5>{!! $fileName !!}</h5>
            <textarea class="form-control" name="config_content" id="config_content_{{ ++$counter }}" cols="30" rows="10">{{ $configContent }}</textarea>
        </div>
    @endforeach
@endif

<form action="/install/confirmation" id="dbSettingsForm" method="POST">
    <a href="/install/email_settings" class="btn btn-primary pull-left">Prev Step</a>
    <div class="form-group">
        <button type="submit" name="confirm" class="btn btn-primary pull-right">Install</button>
    </div>
</form>
@endsection
