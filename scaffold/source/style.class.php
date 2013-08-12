<?php


/**
 *  Stylesheet source functionality
 *  @name    ScaffoldSourceStyle
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldSourceStyle extends Konsolidate
{
	/**
	 *  Minify the given source string
	 *  @name   minify
	 *  @type   method
	 *  @access public
	 *  @param  string source
	 *  @param  bool   debug (add percentage of shrinkage, optional - default false)
	 *  @return string minified source
	 */
	public function minify($source, $debug=false)
	{
		//  basic optimisation expressions
		$length  = strlen($source);
		$pattern = Array(
			'/\/\*(.*)\*\//msU'               => '',     //  block comments
			'/^\s*/sm'                        => '',     //  leading whitespace
			'/([:,;\(])\s*/'                  => '\1',   //  excess whitespace in declarations
			'/\s*([\{])\s*/'                  => '\1',   //  excess whitespace in selectors
			'/(?:[a-z]+[0-9]?)(#)/'           => '\1',   //  remove the element from ID selectors
			'/;\}/'                           => '}',    //  omit the semi-colon from the last property
			'/[a-z0-9\s_\-\.#]+\{\s*\}/i'     => '',     //  remove empty declarations
			'/\b0\.([0-9]+)/'                 => '.\1',  //  decimals between 0 and 1 don't require the leading 0
			'/[\r\n]+/'                       => '',     //  newlines between style rules (make one huge line)
			'/(?:#\w[\w\d]*)\s+(#\w[\w\d]*)/' => '\1',   //  excessive nested ID selectors without (pseudo-)classes
		);
		$source = trim(preg_replace(array_keys($pattern), array_values($pattern), $source));
		return $source . ($debug ? '/*-' . number_format(100 - ((100 / $length) * strlen($source)), 2) . '%*/' : '');
	}
}