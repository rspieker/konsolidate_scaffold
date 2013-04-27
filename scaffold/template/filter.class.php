<?php


/**
 *  Filters to be used from template phase hooks
 *  @name    ScaffoldTemplateFilter
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplateFilter extends Konsolidate
{
	/**
	 *  Remove DOMComment nodes from given dom
	 *  @name   comment
	 *  @type   method
	 *  @access public
	 *  @param  stdClass hook
	 *  @return void
	 */
	public function comment($hook)
	{
		//  remove comments
		$query = '//*[not(name()="script")]/comment()';
		foreach ($hook->xpath->query($query) as $node)
			if (!preg_match('/\[if.*\if\]/', $node->nodeValue)) //  leave IE's conditional comments alone
				$node->parentNode->removeChild($node);
	}

	/**
	 *  Remove empty DOMText nodes from given dom
	 *  @name   whitespace
	 *  @type   method
	 *  @access public
	 *  @param  stdClass hook
	 *  @return void
	 */
	public function whitespace($hook)
	{
		//  compress whitespace not in <pre> or <code> tags (yes, we are aware of the fact that one may style any element as preformatted, however the template engine is about (X)HTML not CSS)
		$query = '//*[not(name()="pre") and not(name()="code")]/text()';
		foreach ($hook->xpath->query($query) as $node)
			if (strtolower($node->parentNode->nodeName) != 'script')
				$node->nodeValue = preg_replace('/\s+/', ' ', $node->nodeValue);

		//  remove whitespace alltogether if it is not in the <body>
		$query = '//*[not(ancestor::body)]/text()';
		foreach ($hook->xpath->query($query) as $node)
			if (trim($node->nodeValue) == '')
				$node->parentNode->removeChild($node);
	}

	/**
	 *  Remove empty attributes
	 *  @name   emptyAttributes
	 *  @type   method
	 *  @access public
	 *  @param  stdClass hook
	 *  @return void
	 */
	public function emptyAttributes($hook)
	{
		foreach ($hook->xpath->query('//@*[.=""]') as $node)
			if ($node->parentNode)
				$node->parentNode->removeAttributeNode($node);
	}
}