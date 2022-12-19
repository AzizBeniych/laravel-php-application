<?php

/**
 * @property string $email
 * @property array|string[] $emails     Virtual field!
 */
trait EmailAttributesTrait
{
	/**
	 * @param  string $value
	 * @return string
	 */
	public function getEmailAttribute($value)
	{
		return implode('; ', $this->getEmailsAttribute($value));
	}

	/**
	 * @param  string $value
	 * @return array|string[]
	 */
	public function getEmailsAttribute($value = null)
	{
		return preg_split('/\s*[,;]\s*/', trim(isset($value) ? $value : $this->email));
	}

	/**
	 * @param  array|string $value
	 */
	public function setEmailsAttribute($value)
	{
		$this->email = implode('; ', is_array($value) ? $value : $this->getEmailsAttribute($value));
	}
}
