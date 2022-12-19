<?php namespace PdfBuilder;

/**
 * FPDI extension that preserves hyperlinks when copying PDF pages.
 * (c) 2012, Andrey Tarantsov <andrey@tarantsov.com>, provided under the MIT license.
 *
 * Published at: https://gist.github.com/2020422
 * @see \TCPDF
 *
 * Note: the free version of FPDI requires unprotected PDFs conforming to spec version 1.4.
 * I use qpdf (http://qpdf.sourceforge.net/) to pre-process PDFs before running through this code, invoking it like this:
 *     qpdf --decrypt --stream-data=uncompress --force-version=1.4 src.pdf temp.pdf
 * then, after processing temp.pdf into out.pdf with FPDI, I run the following to re-establish protection:
 *     qpdf --encrypt "" "" 40 --extract=n -- out.pdf final.pdf
 */
class MyFPDI extends \FPDI
{
	protected $custom_metadata = '';

	/**
	 * {@inheritDoc}
	 */
	public function __construct(
		$orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskCache = false, $pdfA = false)
	{
		parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskCache, $pdfA);

		// prevent powered by message
		$this->tcpdflink = false;

		// set default ColorSpace to CMYK
		//$this->DrawColor = '0 0 0 1 K';
		//$this->FillColor = '0 0 0 1 k';
		//$this->TextColor = '0 0 0 1 k';
		//$this->fgcolor = array('C' => 0, 'M' => 0, 'Y' => 0, 'K' => 100);
		//$this->strokecolor = array('C' => 0, 'M' => 0, 'Y' => 0, 'K' => 100);
		//$this->bgcolor = array('C' => 0, 'M' => 0, 'Y' => 0, 'K' => 0);

		// fix smooth rendering
		$this->custom_metadata = ' /GTS_PDFXVersion(PDF/X-1:2001) /GTS_PDFXConformance(PDF/X-1a:2001)';
		/* @noinspection SpellCheckingInspection */
		$this->custom_xmp = '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">'
			. '<rdf:Description xmlns:pdf="http://ns.adobe.com/pdf/1.3/" xmlns:pdfxid="http://www.npes.org/pdfx/ns/id/" xmlns:pdfx="http://ns.adobe.com/pdfx/1.3/">'
			. '<pdf:Trapped>False</pdf:Trapped>'
			. '<pdfxid:GTS_PDFXVersion>PDF/X-1:2001</pdfxid:GTS_PDFXVersion>'
			. '<pdfx:GTS_PDFXVersion>PDF/X-1:2001</pdfx:GTS_PDFXVersion>'
			. '<pdfx:GTS_PDFXConformance>PDF/X-1a:2001</pdfx:GTS_PDFXConformance>'
			. '</rdf:Description>'
			. '</rdf:RDF>';
	}

	/**
	 * No page header.
	 * {@inheritDoc}
	 */
	public function Header()
	{
	}

	/**
	 * No Page footer.
	 * {@inheritDoc}
	 */
	public function Footer()
	{
	}

	/**
	 * @return float
	 */
	public function getYOffset()
	{
		return ($this->getCellHeightRatio() + 0.5) * $this->getFontSize() / 2;// 0.875
	}

	/**
	 * @return float
	 */
	public function getCellLastY()
	{
		return $this->GetY() - ($this->getCellHeightRatio() - 0.5) * $this->getFontSize() / 2;
	}

	/**
	 * Override to add custom metadata to fix smooth rendering.
	 * {@inheritDoc}
	 */
	protected function _putinfo()
	{
		$oid = $this->_newobj();
		$out = '<<';
		// store current isUnicode value
		$prevIsUnicode = $this->isunicode;
		if ($this->docinfounicode) {
			$this->isunicode = true;
		}
		if (!\TCPDF_STATIC::empty_string($this->title)) {
			// The document's title.
			$out .= ' /Title ' . $this->_textstring($this->title, $oid);
		}
		if (!\TCPDF_STATIC::empty_string($this->author)) {
			// The name of the person who created the document.
			$out .= ' /Author ' . $this->_textstring($this->author, $oid);
		}
		if (!\TCPDF_STATIC::empty_string($this->subject)) {
			// The subject of the document.
			$out .= ' /Subject ' . $this->_textstring($this->subject, $oid);
		}
		if (!\TCPDF_STATIC::empty_string($this->keywords)) {
			// Keywords associated with the document.
			$out .= ' /Keywords ' . $this->_textstring($this->keywords, $oid);
		}
		if (!\TCPDF_STATIC::empty_string($this->creator)) {
			// If the document was converted to PDF from another format, the name of the conforming product that created the original document from which it was converted.
			$out .= ' /Creator ' . $this->_textstring($this->creator, $oid);
		}
		// restore previous isUnicode value
		$this->isunicode = $prevIsUnicode;
		// default producer
		$out .= ' /Producer ' . $this->_textstring(\TCPDF_STATIC::getTCPDFProducer(), $oid);
		// The date and time the document was created, in human-readable form
		$out .= ' /CreationDate ' . $this->_datestring(0, $this->doc_creation_timestamp);
		// The date and time the document was most recently modified, in human-readable form
		$out .= ' /ModDate ' . $this->_datestring(0, $this->doc_modification_timestamp);
		// A name object indicating whether the document has been modified to include trapping information
		$out .= ' /Trapped /False';

		if (!empty($this->custom_metadata)) {
			$out .= $this->custom_metadata;
		}

		$out .= ' >>';
		$out .= "\n" . 'endobj';
		$this->_out($out);
		return $oid;
	}

	/**
	 * Override to reduce generated file size.
	 * {@inheritDoc}
	 */
	protected function _putXMP()
	{
		$oid = $this->_newobj();

		// store current isUnicode value
		$prevIsUnicode = $this->isunicode;
		$this->isunicode = true;
		$prevEncrypted = $this->encrypted;
		$this->encrypted = false;

		/* @var $markChar string */
		$markChar = \TCPDF_FONTS::unichr(0xFEFF, $this->isunicode);
		// set XMP data
		/* @noinspection SpellCheckingInspection */
		$xmp = '<?xpacket begin="' . $markChar . '" id="W5M0MpCehiHzreSzNTczkc9d"?>'
			. '<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Adobe XMP Core 4.2.1-c043 52.372728, 2009/01/18-15:08:04">'
			. '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">';

		//if (!empty($this->title) || !empty($this->author) || !empty($this->subject) || !empty($this->keywords)) {
		$xmp .= '<rdf:Description xmlns:dc="http://purl.org/dc/elements/1.1/">'
			. '<dc:format>application/pdf</dc:format>';
		if (!empty($this->title)) {
			$xmp .= '<dc:title><rdf:Alt><rdf:li xml:lang="x-default">'
				. static::escapeXML($this->title)
				. '</rdf:li></rdf:Alt></dc:title>';
		}
		if (!empty($this->author)) {
			$xmp .= '<dc:creator><rdf:Seq><rdf:li>'
				. static::escapeXML($this->author)
				. '</rdf:li></rdf:Seq></dc:creator>';
		}
		if (!empty($this->subject)) {
			$xmp .= '<dc:description><rdf:Alt><rdf:li xml:lang="x-default">'
				. static::escapeXML($this->subject)
				. '</rdf:li></rdf:Alt></dc:description>';
		}
		if (!empty($this->keywords)) {
			$xmp .= '<dc:subject><rdf:Bag><rdf:li>'
				. static::escapeXML($this->keywords)
				. '</rdf:li></rdf:Bag></dc:subject>';
		}
		$xmp .= '</rdf:Description>';
		//}

		// convert doc creation date format
		$createDate = static::formatXmlDate($this->doc_creation_timestamp);
		// convert doc modification date format
		$modifyDate = static::formatXmlDate($this->doc_modification_timestamp);

		if (isset($createDate)) {
			$xmp .= '<rdf:Description xmlns:xmp="http://ns.adobe.com/xap/1.0/">'
				. '<xmp:CreateDate>' . $createDate . '</xmp:CreateDate>';
			if (!empty($this->creator)) {
				$xmp .= '<xmp:CreatorTool>' . $this->creator . '</xmp:CreatorTool>';
			}
			if (isset($modifyDate)) {
				$xmp .= '<xmp:ModifyDate>' . $modifyDate . '</xmp:ModifyDate>';
			}
			$xmp .= '<xmp:MetadataDate>' . $createDate . '</xmp:MetadataDate>'
				. '</rdf:Description>';
		}

		/* @noinspection XmlUnusedNamespaceDeclaration */
		$xmp .= '<rdf:Description xmlns:pdf="http://ns.adobe.com/pdf/1.3/">';
		if (!empty($this->keywords)) {
			$xmp .= '<pdf:Keywords>' . static::escapeXML($this->keywords) . '</pdf:Keywords>';
		}
		$xmp .= '<pdf:Producer>' . static::escapeXML(\TCPDF_STATIC::getTCPDFProducer()) . '</pdf:Producer>'
			. '</rdf:Description>';

		if (!empty($this->file_id) && strlen($this->file_id) >= 32) {
			$uuid = 'uuid:' . substr($this->file_id, 0, 8) . '-' . substr($this->file_id, 8, 4)
				. '-' . substr($this->file_id, 12, 4) . '-' . substr($this->file_id, 16, 4) . '-' . substr($this->file_id, 20, 12);

			$xmp .= '<rdf:Description xmlns:xmpMM="http://ns.adobe.com/xap/1.0/mm/">'
				. '<xmpMM:DocumentID>' . $uuid . '</xmpMM:DocumentID>'
				. '<xmpMM:InstanceID>' . $uuid . '</xmpMM:InstanceID>'
				. '</rdf:Description>';
		}

		if ($this->pdfa_mode) {
			/* @noinspection SpellCheckingInspection */
			$xmp .= '<rdf:Description xmlns:pdfaid="http://www.aiim.org/pdfa/ns/id/">'
				. '<pdfaid:part>1</pdfaid:part><pdfaid:conformance>B</pdfaid:conformance></rdf:Description>';
		}

		$xmp .= '</rdf:RDF>';
		if (!empty($this->custom_xmp)) {
			$xmp .= $this->custom_xmp;
		}
		/* @noinspection SpellCheckingInspection */
		$xmp .= '</x:xmpmeta><?xpacket end="w"?>';

		/* @noinspection SpellCheckingInspection */
		$out = '<</Type /Metadata /Subtype /XML /Length ' . strlen($xmp) . ">>\nstream\n" . $xmp . "\nendstream\nendobj";
		unset($xmp);

		// restore previous isUnicode value
		$this->isunicode = $prevIsUnicode;
		$this->encrypted = $prevEncrypted;

		$this->_out($out);
		return $oid;
	}

	/**
	 * @param  int $timestamp
	 * @return string
	 */
	protected static function formatXmlDate($timestamp)
	{
		if (empty($timestamp)) {
			return null;
		}

		$d = \TCPDF_STATIC::getFormattedDate($timestamp);
		$s = substr($d, 0, 4) . '-' . substr($d, 4, 2) . '-' . substr($d, 6, 2)
			. 'T' . substr($d, 8, 2) . ':' . substr($d, 10, 2) . ':' . substr($d, 12, 2)
			. substr($d, 14, 3) . ':' . substr($d, 18, 2);

		return static::escapeXML($s);
	}

	/**
	 * @param  string $str
	 * @return string
	 */
	protected static function escapeXML($str)
	{
		return \TCPDF_STATIC::_escapeXML($str);
	}

	/**
	 * @param  string $html
	 * @param  string $allowTags
	 * @return string
	 */
	protected static function cleanHtmlContent(
		$html,
		/* @noinspection HtmlDeprecatedTag */
		$allowTags = '<font><b><i><u><del><sub><sup><ol><ul><li>')
	{
		if (empty($html) || ($html = rtrim($html)) == '') {
			return '';
		}

		// normalize newline
		$html = str_replace("\r", "\n", str_replace("\r\n", "\n", $html));
		$html = preg_replace('#<br\s*/?>([ \t]*\n)?#i', "\n", $html);

		$html = preg_replace('/[ \t]+\n[ \t]*|[ \t]*\n[ \t]+/', "\n", $html);
		$html = preg_replace('/\n\n\n+/', "\n\n", $html);

		$html = strip_tags($html, $allowTags);
		return nl2br($html);
	}

	//region Preserves Hyperlinks Extension

	/**
	 * {@inheritDoc}
	 */
	public function importPage($pageNo, $boxName = 'CropBox', $groupXObject = true)
	{
		$tplIdx = parent::importPage($pageNo, $boxName, $groupXObject);

		$tpl =& $this->_tpls[$tplIdx];
		/* @var $parser \fpdi_pdf_parser */
		$parser =& $tpl['parser'];

		$rp = new \ReflectionProperty('fpdi_pdf_parser', '_pages');
		$rp->setAccessible(true);
		$parserPages = $rp->getValue($parser);

		// look for hyperlink annotations and store them in the template
		/* @noinspection SpellCheckingInspection */
		if (isset($parserPages[$pageNo - 1][1][1]['/Annots'])) {
			/* @noinspection SpellCheckingInspection */
			$annotations = $parserPages[$pageNo - 1][1][1]['/Annots'];
			$annotations = static::resolveStatement($parser, $annotations);

			$links = array();
			foreach ($annotations[1] as $annotation) {
				// all links look like: << /Type /Annot /Subtype /Link /Rect [...] ... >>
				if ($annotation[0] != \pdf_parser::TYPE_DICTIONARY
					|| $annotation[1]['/Type'][1] != '/Annot' || $annotation[1]['/Subtype'][1] != '/Link'
				) {
					continue;
				}

				$rect = $annotation[1]['/Rect'];
				if ($rect[0] == \pdf_parser::TYPE_ARRAY && count($rect[1]) == 4) {
					$x = $rect[1][0][1];
					$y = $rect[1][1][1];
					$x2 = $rect[1][2][1];
					$y2 = $rect[1][3][1];

					$w = $x2 - $x;
					$h = -($y2 - $y);

				} else {
					continue;
				}

				if (isset($annotation[1]['/A'])) {
					$A = $annotation[1]['/A'];
					if ($A[0] == \pdf_parser::TYPE_DICTIONARY && isset($A[1]['/S'])) {
						$S = $A[1]['/S'];

						// << /Type /Annot ... /A << /S /URI /URI ... >> >>
						if ($S[1] == '/URI' && isset($A[1]['/URI'])) {
							$URI = $A[1]['/URI'];
							if (is_string($URI[1])) {
								$url = str_replace("\\000", '', trim($URI[1]));
								if (!empty($url)) {
									$links[] = array($x, $y, $w, $h, $url);
								}
							}
						} elseif ($S[1] == '/GoTo' && isset($A[1]['/D'])) {
							// << /Type /Annot ... /A << /S /GoTo /D [%d 0 R /Fit] >> >>
							$D = $A[1]['/D'];
							if ($D[0] == \pdf_parser::TYPE_ARRAY && count($D[1]) > 0 && $D[1][0][0] == \pdf_parser::TYPE_OBJREF) {
								$targetPageNo = static::findPageNoForRef($parserPages, $D[1][0]);
								if ($targetPageNo >= 0) {
									$links[] = array($x, $y, $w, $h, $targetPageNo);
								}
							}
						}
					}
				} elseif (isset($annotation[1]['/Dest'])) {
					$Dest = $annotation[1]['/Dest'];
					// << /Type /Annot ... /Dest [42 0 R ...] >>
					if ($Dest[0] == \pdf_parser::TYPE_ARRAY && $Dest[0][1][0] == \pdf_parser::TYPE_OBJREF) {
						$targetPageNo = static::findPageNoForRef($parserPages, $Dest[0][1][0]);
						if ($targetPageNo >= 0) {
							$links[] = array($x, $y, $w, $h, $targetPageNo);
						}
					}
				}
			}

			//error_log("Links on page $pageNo:\n" . print_r($links, true));
			$tpl['links'] = $links;
		}

		return $tplIdx;
	}

	/**
	 * @param \fpdi_pdf_parser $parser
	 * @param array $smt
	 * @param int $maxDepth To prevents an infinite recursion on malformed PDFs (not theoretical, actually found in the wild).
	 * @return array
	 */
	protected static function resolveStatement($parser, $smt, $maxDepth = 10)
	{
		if ($maxDepth <= 0) {
			return $smt;
		}

		if ($smt[0] == \pdf_parser::TYPE_OBJREF) {
			//$result = $parser->pdf_resolve_object($parser->_c, $smt);
			$result = $parser->resolveObject($smt);
			return static::resolveStatement($parser, $result, $maxDepth - 1);
		}
		if ($smt[0] == \pdf_parser::TYPE_OBJECT) {
			return static::resolveStatement($parser, $smt[1], $maxDepth - 1);
		}
		if ($smt[0] == \pdf_parser::TYPE_ARRAY) {
			$result = array();
			foreach ($smt[1] as $item) {
				$result[] = static::resolveStatement($parser, $item, $maxDepth - 1);
			}

			$smt[1] = $result;
			return $smt;
		}
		if ($smt[0] == \pdf_parser::TYPE_DICTIONARY) {
			$result = array();
			foreach ($smt[1] as $key => $item) {
				$result[$key] = static::resolveStatement($parser, $item, $maxDepth - 1);
			}

			$smt[1] = $result;
			return $smt;
		}

		return $smt;
	}

	/**
	 * @param array $parserPages
	 * @param array $pageRef
	 * @return int
	 */
	protected static function findPageNoForRef($parserPages, $pageRef)
	{
		foreach ($parserPages as $index => $page) {
			if ($page['obj'] == $pageRef[1] && $page['gen'] == $pageRef[2]) {
				return $index + 1;
			}
		}
		return -1;
	}

	/**
	 * {@inheritDoc}
	 */
	public function useTemplate($tplIdx, $x = null, $y = null, $w = 0, $h = 0, $adjustPageSize = false)
	{
		$result = parent::useTemplate($tplIdx, $x, $y, $w, $h, $adjustPageSize);

		// apply links from the template
		$tpl =& $this->_tpls[$tplIdx];
		if (!empty($tpl['links'])) {
			foreach ($tpl['links'] as $link) {
				// $link[4] is either a string (external URL) or an integer (page number)
				if (is_int($link[4])) {
					$l = $this->AddLink();
					$this->SetLink($l, 0, $link[4]);
					$link[4] = $l;
				}

				$this->Link(
					$link[0] / $this->k + $x,
					$tpl['h'] - ($link[1] - $link[3]) / $this->k + $y,
					$link[2] / $this->k,
					-$link[3] / $this->k,
					$link[4]);
			}
		}

		return $result;
	}

	//endregion

	/*protected function setPageBuffer($page, $data, $append = false)
	{
		// TODO: fix float format in $data
		parent::setPageBuffer($page, $data, $append);
	}

	protected function _putresources()
	{
		parent::_putresources();
	}

	protected function setBuffer($data)
	{
		// TODO: fix float format in $data
		parent::setBuffer($data);
	}*/

}
