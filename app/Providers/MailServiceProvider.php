<?php

namespace App\Providers;

use App\Models\MailSetting;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $viaConsole = false;
        if (strpos(php_sapi_name(), 'cli') !== false) {
            $viaConsole = true; // composer i, and other command, do not need to setup DB for running composer i
        }

        $CheckMailTable = ($viaConsole) ? false : Schema::hasTable('mail_settings');
        if ($CheckMailTable) {
            $emailServices = MailSetting::first();
            if (! empty($emailServices)) {
                $config = [
                    'driver' => $emailServices->mailer_type,
                    'host' => $emailServices->mail_host,
                    'port' => $emailServices->mail_port,
                    'username' => $emailServices->mail_username,
                    'password' => $emailServices->mail_password,
                    'encryption' => null,
                    'from' => [
                        'address' => $emailServices->reply_to,
                        'name' => $emailServices->mail_title,
                    ],
                    //                'sendmail'   => '/usr/sbin/sendmail -bs',
                    //                'pretend'    => false,
                ];

                Config::set('mail', $config);
            }
        }
    }
}
