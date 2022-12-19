<?php namespace PdfBuilder;

interface GeneratorInterface
{
	/**
	 * @param \User $user
	 * @param \CustomPage $template
	 * @param \ThemeLayout $layout
	 * @param array $fields
	 * @return \TCPDF
	 */
	public function generate($user, $template, $layout, $fields);

}
