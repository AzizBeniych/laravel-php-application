<?php

class CustomMailServiceProvider extends \Illuminate\Mail\MailServiceProvider
{

	/**
	 * {@inheritdoc}
	 */
	protected function registerMailgunTransport($config)
	{
		$mailgun = $this->app['config']->get('services.mailgun', array());

		$this->app->bindShared('swift.transport', function() use ($mailgun) {
			return new MailgunV3Transport($mailgun['secret'], $mailgun['domain']);
		});
	}

}
