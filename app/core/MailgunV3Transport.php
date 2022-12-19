<?php

class MailgunV3Transport extends \Illuminate\Mail\Transport\MailgunTransport
{

	/** @var \GuzzleHttp\Client */
	protected $http;

	/**
	 * {@inheritdoc}
	 */
	protected function getHttpClient()
	{
		return isset($this->http) ? $this->http : ($this->http = new \GuzzleHttp\Client(['defaults' => ['verify' => false]]));
	}

	/**
	 * {@inheritdoc}
	 */
	public function setDomain($domain)
	{
		$this->url = 'https://api.mailgun.net/v3/' . $domain . '/messages.mime';

		return $this->domain = $domain;
	}

}
