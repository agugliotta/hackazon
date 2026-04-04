@extends('layouts.installation')

@section('content')
<form action="/install/email_settings" method="POST" id="emailSettingsForm">
    <div class="form-group">
        <label>Mail Engine Type:</label>
        <select name="type" id="mailType" class="form-control">
            <option value="native" {!! isset($type) && $type == 'native' ? 'selected' : '' !!}>Native</option>
            <option value="smtp" {!! isset($type) && $type == 'smtp' ? 'selected' : '' !!}>SMTP</option>
            <option value="sendmail" {!! isset($type) && $type == 'sendmail' ? 'selected' : '' !!}>Sendmail</option>
        </select>
    </div>

    <div class="type-group type-native">
        <div class="form-group">
            <label>mail() function parameters:</label>
            <input class="form-control" type="text" name="mail_parameters" value="{!! isset($mail_parameters) ? $mail_parameters : '' !!}" placeholder='Mail Parameters (defaults to "-f%s")' />
        </div>
    </div>

    <div class="type-group type-sendmail">
        <div class="form-group">
            <label>Sendmail Command:</label>
            <input class="form-control" type="text" name="sendmail_command" value="{!! isset($sendmail_command) ? $sendmail_command : '' !!}" placeholder='Sendmail Command (defaults to "/usr/sbin/sendmail -bs")' />
        </div>
    </div>

    <div class="type-group type-smtp">
        <div class="form-group">
            <label>Hostname:</label>
            <input class="form-control" type="text" name="hostname" value="{!! isset($hostname) ? $hostname : '' !!}" placeholder="Hostname" required />
        </div>

        <div class="form-group">
            <label>Port:</label>
            <input class="form-control" type="text" name="port" value="{!! isset($port) ? $port : '' !!}" placeholder="Port" required />
        </div>

        <div class="form-group">
            <label>Username:</label>
            <input class="form-control" type="text" name="username" value="{!! isset($username) ? $username : '' !!}" placeholder="Username" />
        </div>

        <div class="form-group">
            @php $useExistingPassword = isset($use_existing_password) && $use_existing_password; @endphp
            <label>Password:</label>
            <input class="form-control{!! isset($password) && $password ? ' js-has-existing' : '' !!}"
                   type="password" name="password" value="" placeholder="Password"
                   {{ $useExistingPassword ? 'disabled' : '' }}/><br>
            <label><input type="checkbox" name="use_existing_password" class="js-use-existing-password"
                    {{ $useExistingPassword ? 'checked' : '' }} />
                Use existing password</label><br>
        </div>

        <div class="form-group">
            <p>Encryption:</p>
            <label class="morris-hover-row-label">
                <input class="radio-inline" type="radio" name="encryption" value="" {!! !isset($encryption) || $encryption == '' ? 'checked' : '' !!} /> No encryption
            </label>
            <label>
                <input class="radio-inline" type="radio" name="encryption" value="ssl" {!! isset($encryption) && $encryption == 'ssl' ? 'checked' : '' !!}/> SSL
            </label>
            <label>
                <input class="radio-inline" type="radio" name="encryption" value="tls" {!! isset($encryption) && $encryption == 'tls' ? 'checked' : '' !!}/> TLS
            </label>
        </div>

        <div class="form-group">
            <label>Timeout:</label>
            <input class="form-control" type="text" name="timeout" value="{!! isset($timeout) ? $timeout : '' !!}" placeholder="Timeout (defaults to 5 seconds)" />
        </div>
    </div>

    <div class="form-group">
        <a href="/install/db_settings" class="btn btn-primary pull-left">Prev Step</a>
        <button type="submit" class="btn btn-primary pull-right">Next Step</button>
    </div>
</form>

<script>
    $(function() {
        jQuery(function($) {
            var form = $('#emailSettingsForm'),
                type = form.find('#mailType'),
                $passwordField = $('input[name="password"]'),
                $useExistingPw = $('.js-use-existing-password');

            form.hzBootstrapValidator();

            var updateLayout = function () {
                form.find('.type-group').hide();
                var currentTab = form.find('.type-' + type.val());
                currentTab.show();

                form.find('.type-group:hidden').find('input, select').attr('disabled', 'disabled');
                currentTab.find('input, select').removeAttr('disabled');

                if (form.data('bootstrapValidator')) {
                    form.data('bootstrapValidator').resetForm();
                }
                $useExistingPw.trigger('change');
            };
            updateLayout();

            type.on('change', updateLayout);

            $useExistingPw.on('change', function (ev) {
                if ($useExistingPw.is(':checked')) {
                    $passwordField.attr('disabled', 'disabled');
                } else {
                    $passwordField.removeAttr('disabled');
                }
                form.data('bootstrapValidator').resetForm();
            });
            $useExistingPw.trigger('change');
        });
    });
</script>
@endsection
