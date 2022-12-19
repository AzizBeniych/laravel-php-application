<?php namespace PdfBuilder;

class CleanupProcess
{
	private static $defaultOptions = array(
		'cleanWhiteText' => false,
		'removeTempFiles' => true,
		'replaceTrimBox' => array(),
		'noCompression' => false,
		'onlyCleanMetadata' => false,
	);

	/** @var string PDF file path. */
	protected $file;
	/** @var array */
	protected $options;

	/** @var string */
	protected $data;

	/**
	 * @param  string $file PDF file path.
	 * @param  array $options
	 */
	public function __construct($file, $options = array())
	{
		if (empty($file) || !file_exists($file) || substr_compare($file, '.pdf', -4, 4, true) != 0)
			throw new \InvalidArgumentException('Invalid PDF file name: ' . $file);

		if (!function_exists('shell_exec'))
			throw new \RuntimeException('Can\'t process PDF without "shell_exec" function');

		$this->file = $file;
		$this->options = array_merge(static::$defaultOptions, is_array($options) ? $options : array());
	}

	/**
	 * @param  string|array $debug Logging callback.
	 * @throws \Exception
	 */
	public function execute($debug = 'print_r')
	{
		if (empty($this->options['onlyCleanMetadata'])) {

			$out = $this->normalize($this->file, $path = $this->file . '.src');
			// DEBUG
			if (!empty($out) && !empty($debug))
				call_user_func($debug, $out);

			$this->cleanup($path, $dest = $this->file . '.fix');
		}

		// Backup
		if (!@rename($this->file, $path = $this->file . '.bak'))
			throw new \Exception('Can\'t backup PDF file: ' . $this->file);

		if (isset($dest)) {

			$out = $this->compress($dest, $this->file);
			// DEBUG
			if (!empty($out) && !empty($debug))
				call_user_func($debug, $out);

		} else {
			$this->cleanMetadata($path, $this->file);
		}

		// Remove temporary files
		if (!empty($this->options['removeTempFiles'])) {
			foreach (glob($this->file . '.*') as $fn)
				@unlink($fn);
		}
	}

	/**
	 * Only clean Metadata of the PDF file.
	 *
	 * @param  string $src Source PDF file.
	 * @param  string $dest Output file path.
	 * @throws \Exception
	 */
	public function cleanMetadata($src, $dest)
	{
		$data = @file_get_contents($src);
		if (!$data)
			throw new \Exception('Can\'t read PDF file: ' . $src);

		$data = preg_replace_callback('#(\s<<.*?)/Metadata \d+ 0 R\b#', function ($m) {
			return str_pad($m[1], strlen($m[0]));
		}, $this->data = $data, 1);

		$error = null;
		if (!$data) {
			$error = 'Failed to remove PDF Metadata tag';
		} else {
			$this->data = $data;
			unset($data);
		}

		// Save cleaned file
		$this->flushData($dest, $error);
	}

	/**
	 * @param  string $src Source PDF file.
	 * @param  string $dest Output file path.
	 * @return string
	 * @throws \Exception
	 */
	public function normalize($src, $dest)
	{
		// /usr/bin/qpdf
		$out = shell_exec(
			'qpdf --decrypt --normalize-content=y --stream-data=uncompress --newline-before-endstream --coalesce-contents --force-version=1.3 '
			. escapeshellarg($src) . ' ' . escapeshellarg($dest) . ' 2>&1');

		if (isset($out))
			$out = trim($out);

		// Error ?
		if (!file_exists($dest))
			throw new \Exception('Can\'t normalize PDF file: ' . $src . PHP_EOL . $out);

		return $out;
	}

	/**
	 * @param  string $src Source PDF file.
	 * @param  string $dest Output file path.
	 * @return string
	 * @throws \Exception
	 */
	public function compress($src, $dest)
	{
		$cmd = empty($this->options['noCompression'])
			? '--normalize-content=n --stream-data=compress' : '--normalize-content=y --stream-data=uncompress';

		$out = shell_exec(
			'qpdf --decrypt ' . $cmd . ' --newline-before-endstream --ignore-xref-streams --force-version=1.3 '
			. escapeshellarg($src) . ' ' . escapeshellarg($dest) . ' 2>&1');

		if (isset($out))
			$out = trim($out);

		// Error ?
		if (!file_exists($dest))
			throw new \Exception('Can\'t compress PDF file: ' . $src . PHP_EOL . $out);

		return $out;
	}

	/**
	 * @param  string $src Source PDF file.
	 * @param  string $dest Output file path.
	 * @throws \Exception
	 */
	public function cleanup($src, $dest)
	{
		$data = @file_get_contents($src);
		if (!$data)
			throw new \Exception('Can\'t read PDF file: ' . $src);

		// Clean text in PDF
		$data = preg_replace_callback('/ obj\s<<[^<>]*(?:<<[^<>]*(?:<<.*?>>[^<>]*)*>>[^<>]*)*>>\s/s',
			array($this, 'cleanupPdfTags'), $this->data = $data);
		if (!$data)
			$this->flushData($dest, 'Failed to clean PDF tags');

		// Clean PDF contents
		if (preg_match_all('#\s<<[^<>]*/Contents (\d+) 0 R\b#', $this->data = $data, $matches)) {
			foreach ($matches[1] as $i => $m) {
				$data = preg_replace_callback(
					'/(\s' . $m . ' 0 obj\s+<<.*?>>\s+stream\s)(.*?)(\sendstream\s+endobj)/s',
					array($this, 'cleanPdfContent'), $this->data = $data);

				if (!$data)
					$this->flushData($dest, 'Failed to clean PDF content: ' . $i);
			}
		}

		// Remove unused fonts
		if (!empty($this->options['cleanWhiteText'])) {
			$data = preg_replace_callback(
				'#(\s<<[^<>]*/Contents )(\d+)( 0 R\b[^<>]*/Resources\s*<<[^<>]*(?:<<[^<>]*>>[^<>]*)*/Font\s*<<)(.*?)>>#s',
				array($this, 'cleanUnusedFonts'), $this->data = $data);

			if (!$data)
				$this->flushData($dest, 'Failed to remove unused PDF fonts');
		}

		// Try to use built-in ColorSpace
		$this->data = $data;
		unset($data);
		$this->replaceColorSpace($dest);

		// Save cleaned file
		$this->flushData($dest);
	}

	/**
	 * @param  string $dest Output file path.
	 * @param  string $error
	 * @throws \Exception
	 */
	protected function flushData($dest, $error = '')
	{
		if (!empty($this->data)) {
			// Flush to file
			$f = @fopen($dest, 'wb');
			if (!$f)
				throw new \Exception('Can\'t write PDF file: ' . $dest);

			fwrite($f, $this->data);
			@fclose($f);

			// Free memory
			unset($this->data);
		}

		if (!empty($error))
			throw new \Exception($error);
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected function cleanupPdfTags($matches)
	{
		$s = preg_replace_callback('#/(?:Metadata|AcroForm|PageLabels) \d+ 0 R\s#', 'static::fillSpaces', $matches[0]);
		$s = preg_replace_callback('#/(?:OutputIntents|BleedBox|ArtBox)\s*\[.*?\]#s', 'static::fillSpaces', $s);
		// Try to add PDF/X metadata
		$fn = (strpos($s, 'PDF/X') === false) ? 'static::fillPdfXTag' : 'static::fillSpaces';
		$s = preg_replace_callback('#/(Creator)\s*\([^()]*(?:\([^()]*\)[^()]*)*\)#', $fn, $s);
		$s = preg_replace_callback('#/(Producer|Title)\s*\(.*?\)#s', $fn, $s);
		//
		$s = preg_replace_callback('#/Rotate 0\b#', 'static::fillSpaces', $s);
		$s = preg_replace_callback('#/(?:Properties|ExtGState)\s*<<.*?>>#s', 'static::fillSpaces', $s);
		$s = preg_replace_callback('#/Name\s*/[Xx]\b#', 'static::fillSpaces', $s);
		$s = preg_replace_callback('#/DecodeParms\s*\[\s*<<\s*>>\s*\]#s', 'static::fillSpaces', $s);

		$s = preg_replace_callback('#(/FontFamily\s*\()[A-Z]+\+(.*?)(\))#s', 'static::camelToWords', $s);

		$s = preg_replace('#(/Filter\s*)\[(\s*/DCTDecode\s*)\]#s', '$1 $2 ', $s);

		$s = preg_replace_callback('# (/DescendantFonts) (\d+) 0 R #', array($this, 'replaceRefTag'), $s);

		$s = preg_replace('/\.0(\s+|\])/', '  $1', $s);

		// Auto-fix /CropBox
		if (preg_match('#/CropBox\s*\[(.*?)\]#s', $s, $mc)
			&& preg_match('#/MediaBox\s*\[(.*?)\]#s', $s, $mm)
			&& preg_match('#/TrimBox\s*\[(.*?)\]#s', $s, $mt)
			&& $mc[1] === $mm[1] && $mc[1] !== $mt[1]
		) {
			$s = str_replace($mc[0], str_pad('/CropBox [' . trim($mt[1]) . ']', strlen($mc[0])), $s);

			// Try to fix /TrimBox
			if (count($r = $this->options['replaceTrimBox']) > 1
				&& ($ss = preg_replace($r[0], $r[1], $mt[1])) && $ss != $mt[1]
			) {
				$s = str_replace($mt[0], str_pad('/TrimBox [' . $ss . ']', strlen($mt[0])), $s);
			}
		}

		return $s;
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected function cleanPdfContent($matches)
	{
		$s = preg_replace('#/GS\d+ gs\s#', '', $matches[2]);
		$s = preg_replace('#/(?:PlacedGraphic|PlacedPDF|TextClip|MC\d+)\s#', '', $s);
		$s = preg_replace('#/(?:Figure|Span)\s*<<[^<>]*(?:<[^<>]*>[^<>]*)*>>#s', '', $s);

		$s = preg_replace('/\s(?:BDC|EMC|BMC)\s/', ' ', $s);

		// Clean white text
		if (!empty($this->options['cleanWhiteText'])) {
			$s = preg_replace_callback('/(\s0\s+0\s+0\s+0\s+k\b.*?)(\s[\d.]+\s+[\d.]+\s+[\d.]+\s+[\d.]+\s+k|\sET)\b/s',
				'static::cleanWhiteText', $s);
		}

		return $matches[1] . $s . $matches[3];
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected function replaceRefTag($matches)
	{
		if (preg_match('/\s' . $matches[2] . ' 0 obj\s+\[\s*(.*?)\s*\]/s', $this->data, $m))
			return str_pad($matches[1] . '[' . $m[1] . ']', strlen($matches[0]));

		return $matches[0];
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected function cleanUnusedFonts($matches)
	{
		$s = $matches[4];

		// Content ID
		if (preg_match('/\s' . $matches[2] . ' 0 obj\s+<<.*?>>\s+stream\s(.*?)\sendstream\s+endobj/s', $this->data, $m)) {
			$c = $m[1];

			// Font IDs
			if (preg_match_all('#(/[A-Z]\w*\d+) \d+ 0 R\b#', $s, $m)) {
				foreach ($m[1] as $i => $f) {

					if (!preg_match('#' . $f . '\s#', $c)) {
						$ss = $m[0][$i];
						$s = str_replace($ss, str_repeat(' ', strlen($ss)), $s);
					}
				}
			}

			unset($c, $m);
		}

		return $matches[1] . $matches[2] . $matches[3] . $s . '>>';
	}

	/**
	 * @param  string $dest Output file path.
	 * @throws \Exception
	 */
	protected function replaceColorSpace($dest)
	{
		if (preg_match_all('#\s(\d+) 0 obj\s+\[[^\[\]]*/ICCBased (\d+) 0 R\b#', $this->data, $matches)) {
			foreach ($matches[2] as $i => $m) {

				// sRGB 3144
				if (preg_match('#\s' . $m . ' 0 obj\s+<<[^<>]*/Length 3\d{3}\b#', $this->data)) {
					$data = preg_replace('/(\s<<.*?) ' . $matches[1][$i] . ' 0 R\b/', '$1 /DeviceRGB', $this->data);

					if (!$data) {
						$this->flushData($dest, 'Failed to replace sRGB ColorSpace: ' . $i);
					} else {
						$this->data = $data;
						unset($data);
					}
				}
			}
		}
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected static function cleanWhiteText($matches)
	{
		if (substr($matches[2], 1, 2) === 'ET')
			return $matches[2];

		$s = ' ';
		if (preg_match_all('#/[A-Z]\w*\d+\s+[\d.]+\s+Tf\b#', $matches[1], $m))
			$s .= $m[0][count($m[0]) - 1];

		return $s . $matches[2];
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected static function fillSpaces($matches)
	{
		return str_repeat(' ', strlen($matches[0]));
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected static function fillPdfXTag($matches)
	{
		$l = strlen($matches[0]);
		if ($matches[1] == 'Creator' && $l > 35)
			return str_pad('/GTS_PDFXConformance(PDF/X-1a:2001)', $l);

		if ($matches[1] == 'Producer' && $l > 30)
			return str_pad('/GTS_PDFXVersion(PDF/X-1:2001)', $l);

		return str_repeat(' ', $l);
	}

	/**
	 * @param  array
	 * @return string
	 */
	protected static function camelToWords($m)
	{
		$s = implode(' ', preg_split('/(?<=[A-Z])(?=[A-Z][a-z])|(?<=[a-z])(?=[A-Z])/x', $m[2]));
		return str_pad($m[1] . str_replace('-', ' ', $s) . $m[3], strlen($m[0]));
	}

}
