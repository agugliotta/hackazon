<?php
/**
 * Created by IntelliJ IDEA.
 * User: Nikolay Chervyakov 
 * Date: 08.09.2014
 * Time: 12:45
 */


namespace App\Installation\Step;


class EmailSettingsStep extends AbstractStep
{
    protected $template = 'installation/emailsettings';

    /**
     * @var string Possible variants: 'smtp', 'sendmail' or 'native'
     */
    protected $type = 'sendmail';

    // PHP Mail settings
    /**
     * @var string Defaults to "-f%s"
     */
    protected $mail_parameters;

    // Sendmail settings
    /**
     * @var string Defaults to "/usr/sbin/sendmail -bs"
     */
    protected $sendmail_command;

    // SMTP settings
    protected $hostname = 'localhost';

    protected $port = 25;

    protected $username;

    protected $password;

    protected $encryption;

    protected $timeout;

    protected $useExistingPassword;

    protected $defaultPassword;

    protected function processRequest(array $data = [])
    {
        $this->isValid = false;

        $this->type = $data['type'];

        if (!in_array($this->type, $this->getValidTypes())) {
            $this->errors[] = 'Please select correct type.';
            return false;
        }

        if ($this->type == 'native') {
            $this->mail_parameters = $data['mail_parameters'] ?: null;

        } else if ($this->type == 'sendmail') {
            $this->sendmail_command = $data['sendmail_command'] ?: null;

        } else if ($this->type == 'smtp') {
            $this->useExistingPassword = (bool) $data['use_existing_password'];
            $this->hostname = $data['hostname'];
            $this->port = $data['port'];
            $this->username = $data['username'] ?: null;
            $this->password = $this->useExistingPassword ? $this->defaultPassword : $data['password'];
            $this->encryption = $data['encryption'] ?: null;
            $this->timeout = $data['timeout'] ?: null;

            if (!$data['hostname']) {
                $this->errors[] = 'Please enter hostname.';
            }

            if (!$data['port']) {
                $this->errors[] = 'Please enter port.';
            }

            if ($this->encryption && !in_array($this->encryption, ['ssl', 'tls'])) {
                $this->errors[] = 'Please enter correct encryption.';
            }
        }

        if (count($this->errors)) {
            return false;
        }

        $this->isValid = true;
        return true;
    }

    protected function persistFields()
    {
        return ['type', 'mail_parameters', 'sendmail_command', 'username', 'password', 'hostname', 'port',
            'encryption', 'timeout', 'useExistingPassword', 'defaultPassword'];
    }

    public function init()
    {
        $emailConfig = config('email.default') ?: [];

        $this->hostname         = $emailConfig['hostname'] ?? config('mail.mailers.smtp.host', '127.0.0.1');
        $this->port             = $emailConfig['port'] ?? config('mail.mailers.smtp.port', 2525);
        $this->username         = $emailConfig['username'] ?? config('mail.username', '');
        $this->password         = $emailConfig['password'] ?? config('mail.password', '');
        $this->encryption       = $emailConfig['encryption'] ?? '';
        $this->timeout          = $emailConfig['timeout'] ?? 30;
        $this->type             = $emailConfig['type'] ?? 'smtp';
        $this->mail_parameters  = $emailConfig['mail_parameters'] ?? '';
        $this->sendmail_command = $emailConfig['sendmail_command'] ?? '/usr/sbin/sendmail -bs';

        $this->defaultPassword = $this->password;
    }

    public function getViewData()
    {
        return [
            'hostname' => $this->hostname,
            'port' => $this->port,
            'username' => $this->username,
            'password' => $this->password,
            'encryption' => $this->encryption,
            'timeout' => $this->timeout,
            'type' => $this->type,
            'mail_parameters' => $this->mail_parameters,
            'sendmail_command' => $this->sendmail_command,
            'use_existing_password' => $this->useExistingPassword,
        ];
    }

    public function getValidTypes()
    {
        return ['smtp', 'sendmail', 'native'];
    }
} 