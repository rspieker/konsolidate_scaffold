<?php


/**
 *  Script source functionality
 *  @name    ScaffoldSourceScript
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldSourceScript extends Konsolidate
{
	/**
	 *  Minify the given source string
	 *  @name   minify
	 *  @type   method
	 *  @access public
	 *  @param  string source
	 *  @param  bool   debug (add percentage of shrinkage, optional default false)
	 *  @return string minified source
	 */
	public function minify($source, $debug=false)
	{
		//  basic optimisation expressions
		$length  = strlen($source);
		$pattern = Array(
			'/\/\*(.*)\*\//msU'                                                         => '',       //  block comments
			'/\/\/.*/'                                                                  => '',       //  single line comments
			'/^\s*/sm'                                                                  => '',       //  leading whitespace
			'/\s*([=\-\+&\|,\?:<>\*%!;\/]+)\s*/sm'                                      => '\1',     //  operator whitespace
			'/\s+([\{\}\[\]\(\)]+)/'                                                    => '\1',     //  whitespace before brackets and braces
			'/([\{\[\(\)]+)\s+/'                                                        => '\1',     //  whitespace after opening brackets and opening/closing braces
			'/;([\}]+)/'                                                                => '\1',     //  removing semi-colons before closing brackets
			'/\b([a-z]+)[\r\n]+([a-z_]+)\b/i'                                           => '\1 \2',  //  remove linefeeds between keywords
			'/([\}]+)[\r\n]+(return|function|if|for|while|break|delete|else|var|;)\b/i' => '\1\2',   //  remove linefeeds between closing brace and keywords
			'/([\}]+)[\r\n]+([a-z_]+)\b/i'                                              => '\1;\2',  //  remove whitespace between closing braces and anything left
		);
		$source = trim(preg_replace(array_keys($pattern), array_values($pattern), $source));
		return $source . ($debug ? '/*-' . number_format(100 - ((100 / $length) * strlen($source)), 2) . '%*/' : '');
	}
}