<?php

namespace NickFairchild\Mail;

use Swift_Mailer;
use Illuminate\Mail\Mailer;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Mail\Markdown;
use Illuminate\Mail\TransportManager;
use Illuminate\Support\ServiceProvider;

class MailServiceProvider extends ServiceProvider
{
    protected $defer = true;

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->registerSwiftMailer();

        $this->registerMailer();

        $this->registerMarkdownRenderer();
    }

    /**
     * Register the mailer instance.
     */
    protected function registerMailer()
    {
        $this->app->singleton('mailer', function ($app) {
            $config = $app->make('config')->get('mail');

            $mailer = new Mailer(
                $app['view'], $app['swift.mailer']
            );

            foreach (['from', 'reply_to', 'to'] as $type) {
                $this->setGlobalAddress($mailer, $config, $type);
            }

            return $mailer;
        });
    }

    /**
     * Set a global address on the mailer by type.
     *
     * @param       $mailer
     * @param array $config
     * @param       $type
     */
    protected function setGlobalAddress($mailer, array $config, $type)
    {
        $address = Arr::get($config, $type);

        if (is_array($address) && isset($address['address'])) {
            $mailer->{'always' . Str::studly($type)}($address['address'], $address['name']);
        }
    }

    /**
     * Register the swift mailer instance.
     */
    public function registerSwiftMailer()
    {
        $this->registerSwiftTransport();

        $this->app->singleton('swift.mailer', function ($app) {
            return new Swift_Mailer($app['swift.transport']->driver());
        });
    }

    /**
     * Register the swift transport instance.
     */
    protected function registerSwiftTransport()
    {
        $this->app->singleton('swift.transport', function ($app) {
            return new TransportManager($app);
        });
    }

    /**
     * Register the markdown renderer instance.
     */
    protected function registerMarkdownRenderer()
    {
        $this->app->singleton(Markdown::class, function () {
            return new Markdown($this->app->make('view'), [
                'theme' => config('mail.markdown.theme', 'default'),
                'paths' => config('mail.markdown.paths', []),
            ]);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [
            'mailer', 'swift.mailer', 'swift.transport', Markdown::class,
        ];
    }
}