<?php

/**
 * Custom validator resolver.
 *
 * @property \Illuminate\Foundation\Application $container
 */
class CustomValidator extends \Mews\Captcha\CaptchaValidator
{

	/**
	 * {@inheritDoc}
	 */
	protected function validateRequired($attribute, $value)
	{
		if (is_null($value) || $value === '') {
			return false;
		} elseif ((is_array($value) || $value instanceof \Countable) && count($value) < 1) {
			return false;
		} elseif ($value instanceof \Symfony\Component\HttpFoundation\File\File) {
			return (string)$value->getPath() != '';
		}

		return true;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function validateMimes($attribute, $value, $parameters)
	{
		if (!$this->isAValidFileInstance($value)) {
			return false;
		}

		if ($value->getPath() == '') {
			return false;
		}
		if (function_exists('finfo_open') && in_array($value->guessExtension($value), $parameters)) {
			return true;
		}

		if ($value instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
			return $value->guessClientExtension();
		}
		return false;
	}

	/**
	 * {@inheritDoc}
	 */
	protected function validateUrl($attribute, $value)
	{
		return (filter_var($value, FILTER_VALIDATE_URL) !== false
			|| filter_var("http://$value", FILTER_VALIDATE_URL) !== false);
	}

	/**
	 * Register new custom jQuery validation rules.
	 *
	 * @param \Bllim\Laravalid\Converter\Base\Rule $rule
	 * @param \Bllim\Laravalid\Converter\Base\Message $message
	 */
	public static function extendLaravalid($rule, $message)
	{
		$rule->extend('not_blank', function () {
			return ['required' => 'required', 'data-rule-notblank' => 'true'];
		});
		$rule->extend('not_match', function ($parsedRule) use ($rule) {
			/* @var $rule \Bllim\Laravalid\Converter\JqueryValidation\Rule */
			$attributes = $rule->regex($parsedRule);
			return ['data-rule-notmatch' => $attributes['pattern']];
		});
		$rule->extend('max_words', function ($parsedRule) {
			return ['data-rule-maxwords' => reset($parsedRule['parameters'])];
		});
		$rule->extend('min_words', function ($parsedRule) {
			return ['data-rule-minwords' => reset($parsedRule['parameters'])];
		});
		$rule->extend('username', function () {
			return ['data-rule-username' => 'true'];
		});
		$rule->extend('emails', function () {
			return ['data-rule-emails' => 'true'];
		});

		$message->extend('not_blank', function ($parsedRule, $attribute) use ($message) {
			$msg = $message->getValidationMessage($attribute, $parsedRule['name']);
			return ['data-msg-required' => $msg, 'data-msg-notblank' => $msg];
		});
		$message->extend('not_match', function ($parsedRule, $attribute) use ($message) {
			$msg = $message->getValidationMessage($attribute, $parsedRule['name']);
			return ['data-msg-notmatch' => $msg];
		});
		$message->extend('max_words', function ($parsedRule, $attribute) use ($message) {
			$msg = $message->getValidationMessage($attribute, $parsedRule['name'], ['max' => '{0}']);
			return ['data-msg-maxwords' => $msg];
		});
		$message->extend('min_words', function ($parsedRule, $attribute) use ($message) {
			$msg = $message->getValidationMessage($attribute, $parsedRule['name'], ['min' => '{0}']);
			return ['data-msg-minwords' => $msg];
		});
	}

	public function validateNotBlank(/* @noinspection PhpUnusedParameterInspection */$attribute, $value)
	{
		return !is_string($value) || trim($value) !== '';
	}

	public function validateNotMatch(/* @noinspection PhpUnusedParameterInspection */$attribute, $value, $parameters)
	{
		$this->requireParameterCount(1, $parameters, 'not_match');

		return empty($value) || !preg_match($parameters[0], $value);
	}

	// @see /jquery-validate/additional-methods
	public function validateMaxWords(/* @noinspection PhpUnusedParameterInspection */$attribute, $value, $parameters)
	{
		$this->requireParameterCount(1, $parameters, 'max_words');

		if (empty($value) || ($value = trim($value)) === '') {
			return ($parameters[0] >= 0);
		}

		return (html_count_words($value) <= $parameters[0]);
	}

	public function validateMinWords(/* @noinspection PhpUnusedParameterInspection */$attribute, $value, $parameters)
	{
		$this->requireParameterCount(1, $parameters, 'min_words');

		if (empty($value) || ($value = trim($value)) === '') {
			return ($parameters[0] <= 0);
		}

		return (html_count_words($value) >= $parameters[0]);
	}

	protected function replaceMaxWords($message, $attribute, $rule, $parameters)
	{
		return $this->replaceMax($message, $attribute, $rule, $parameters);
	}

	protected function replaceMinWords($message, $attribute, $rule, $parameters)
	{
		return $this->replaceMin($message, $attribute, $rule, $parameters);
	}

	public static function validateUsername(/* @noinspection PhpUnusedParameterInspection */$attribute, $value)
	{
		return preg_match('/^[a-zA-Z0-9.!#$%&\'*+\/=?@^_`{|}~-]+$/', $value);
	}

	public static function validateEmails(/* @noinspection PhpUnusedParameterInspection */$attribute, $value)
	{
		foreach (preg_split('/\s*[,;]\s*/', $value) as $s) {
			if (filter_var($s, FILTER_VALIDATE_EMAIL) === false)
				return false;
		}
		return true;
	}

}
