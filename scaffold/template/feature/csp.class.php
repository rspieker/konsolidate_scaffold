<?php


/**
 *  Content Security Policy Template Feature, handles the <k:csp /> feature which automatically complements both
 *  script-src and style-src rules based on file requirements (<k:require file="..." />)
 *  @name    ScaffoldTemplateFeatureCSP
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplateFeatureCSP extends ScaffoldTemplateFeature
{
	protected $_policy;

	/**
	 *  Do all preparations needed for the feature to do its deed
	 *  @name   prepare
	 *  @type   method
	 *  @access public
	 *  @return bool success
	 */
	public function prepare()
	{
		$policyList = Array(
			'src' => Array(
				'default',
				'script',
				'style',
				'img',
				'media',
				'frame',
				'font',
				'connect'
			),
			'uri' => Array(
				'report'
			)
		);

		foreach ($policyList as $type=>$list)
			foreach ($list as $policy)
				$this->_addPolicy($policy . '-' . $type, isset($this->{$policy}) ? $this->{$policy} : ($policy == 'default' ? 'none' : null), true);

		return parent::prepare();
	}

	/**
	 *  Render the feature (and send the policy header)
	 *  @name   render
	 *  @type   method
	 *  @access public
	 *  @return bool success
	 */
	public function render()
	{
		//  obtain all required files and process the javascript/stylesheet urls
		$requires = $this->_template->getFeatures('require', null, true);
		foreach ($requires as $require)
		{
			$type = false;
			switch ($require->type)
			{
				case 'text/css':
					$this->_addPolicy('style-src', $require->file);
					break;

				case 'text/javascript':
					$this->_addPolicy('script-src', $require->file);
					break;
			}
		}

		//  clean up the internal policy buffer
		$this->_optimizePolicy();

		//  if there are any policy rules, create the header contents
		if (count($this->_policy))
		{
			$content = '';
			foreach ($this->_policy as $type=>$sources)
				if (count($sources))
					$content .= (!empty($content) ? '; ' : '') . $type . ' ' . implode(' ', array_keys($sources));

			//  send the headers if header content was created
			if (!empty($content))
				$this->_sendHeader($content);
		}

		//  let the parent class clean up the feature node
		return parent::render();
	}

	/**
	 *  Obtain a valid security domain policy for given input
	 *  @name   _getDomain
	 *  @type   method
	 *  @access protected
	 *  @param  string policy domain
	 *  @return string policy
	 */
	protected function _getDomain($input)
	{
		if (empty($input))
			return false;

		$keyword = Array(
			//  data-urls
			'data',
			//  shorthand for the current domain
			'self',
			//  allow nothing
			'none',
			//  allow inline content (inline javascript sources and event handlers, inline styles)
			'unsafe-inline',
			//  allow evaluating functions (javascript eval, new Function, setTimeout("..."))
			'unsafe-eval',
			//   allow anything from any https origin
			'https'
		);
		$url = parse_url($input);

		if (isset($url['path']))
		{
			if (isset($url['host']))
				return $url['host'];

			//  keyword in path
			else if (in_array($url['path'], $keyword))
				return $url['path'];

			//  keyword in scheme (data)
			else if (isset($url['scheme']) && in_array($url['scheme'], $keyword))
				return $url['scheme'];

			//  domains
			else if (preg_match('/^(\*\.)?[a-z]\w+\.[a-z]{2,6}$/', $url['path']))
				return $url['path'];

			//  protocol-less domain and plain host names
			if (preg_match('/^(\/\/)?[a-z].*[^\/]$/', $url['path'], $match))
			{
				$url = parse_url('http' . (isset($_SERVER['HTTPS']) ? 's' : '') . ':' . $url['path']);
				if (isset($url['host']))
					return $url['host'];
			}
		}
		return 'self';
	}

	/**
	 *  Obtain the properly quoted form of the policy rule
	 *  @name   _quoteRule
	 *  @type   method
	 *  @access protected
	 *  @param  string rule
	 *  @return string rule
	 */
	protected function _quoteRule($rule)
	{
		if (isset($_SERVER['SERVER_NAME']) && $rule == $_SERVER['SERVER_NAME'])
			$rule = 'self';

		switch ($rule)
		{
			case 'data':
				$rule .= ':';
				break;

			case 'self':
			case 'none':
			case 'unsafe-inline':
			case 'unsafe-eval':
				$rule = '\'' . $rule . '\'';
				break;
		}

		return $rule;
	}

	/**
	 *  Add a rule to the policy
	 *  @name   _addPolicy
	 *  @type   method
	 *  @access protected
	 *  @param  string policy
	 *  @param  string rules
	 *  @return void
	 */
	protected function _addPolicy($policy, $rules)
	{
		$ruleList = explode(' ', $rules);
		if (count($ruleList))
		{
			if (!is_array($this->_policy))
				$this->_policy = Array();

			if (!isset($this->_policy[$policy]))
				$this->_policy[$policy] = Array();

			foreach ($ruleList as $rule)
			{
				$rule = $this->_quoteRule($this->_getDomain($rule));
				if (!empty($rule))
					$this->_policy[$policy][$rule] = true;
			}
		}
	}

	/**
	 *  Optimize the rules in the policy buffer, removing any rule covered by other rules/policies
	 *  @name   _optimizePolicy
	 *  @type   method
	 *  @access protected
	 *  @return void
	 */
	protected function _optimizePolicy()
	{
		$wildcard = '/^[a-z0-9\._-]+(\.[a-z]\w+\.[a-z]{2,6})$/';
		$default  = $this->_policy['default-src'];
		ksort($default);
		foreach ($this->_policy as $type=>$list)
			if ($type !== 'default-src')
			{
				ksort($list);
				if ($default == $list || count($list) <= 0)
				{
					unset($this->_policy[$type]);
				}
				else
				{
					//  remove duplicates (and wilcard matches)
					foreach ($list as $host=>$enabled)
						if (!$enabled)
							unset($this->_policy[$type][$host]);
						else if (preg_match($wildcard, $host, $match) && isset($list['*' . $match[1]]))
							unset($this->_policy[$type][$host]);

					//  determine if all of the rules are already covered by the default policy
					$allDefault = true;
					foreach ($this->_policy[$type] as $host=>$enabled)
						if (!isset($default[$host]) && (!preg_match($wildcard, $host, $match) || !isset($default['*' . $match[1]])))
						{
							$allDefault = false;
							break;
						}

					//  if all rules are covered by the default policy, we don't need to set it
					if ($allDefault)
						unset($this->_policy[$type]);
				}
			}
	}

	/**
	 *  Send the policy header, crafted to the (advertised) user-agent
	 *  @name   _sendHeader
	 *  @type   method
	 *  @access protected
	 *  @param  string policy header
	 *  @return void
	 */
	protected function _sendHeader($content)
	{
		//  default to the most recent header version
		$header = 'Content-Security-Policy';
		$agent  = $this->call('/Tool/serverVal', 'HTTP_USER_AGENT');

		//  A crude form of User-Agent based browser detection
		if (!empty($agent) && preg_match_all('/(?:(?:firefox|chrome|safari|msie|version|gecko|webkit|trident)[\/ ][0-9\.]+)/i', $agent, $match))
		{
			$version = (object) Array();
			foreach ($match[0] as $part)
			{
				list($key, $value) = explode('/', $part, 2);
				$version->{strtolower($key)} = $value;
			}

			//  Using the detected user-agent to determine which of the three CSP header flavors we need
			//  based on: http://caniuse.com/contentsecuritypolicy (2013-08-09)
			//  - X-Content-Security-Policy: MSIE versions (10+), FireFox versions 4-22 (slight differences from spec)
			//  - X-Webkit-CSP: Safari versions (5.1+), Chrome versions 14-24
			//  - Content-Security-Policy: Chrome 25+, FireFox 23+ (CSP spec 1.0)

			//  Note that the most appropriate headers are send for browser versions which don't actually support them,
			//  e.g. MSIE 9- and FireFox 3- will receive the X-Content-Security-Policy headers
			//  Any user-agent unmatched by the criteria belowwill receive the Content-Security-Policy header

			//  All versions of IE > 10 and Firefox up to 22 need the X- prefixed header
			if (isset($version->msie) || (isset($version->firefox) && $version->firefox < 23))
			{
				$header = 'X-' . $header;
				//  As Firefox implemented their initial CSP spec, we need to reflect the original
				//  policy rules (pre-w3 spec)
				if (isset($version->firefox))
				{
					//  connect-src is called xhr-src (and only applies to XMLHTTPRequest
					$content = str_replace('connect-src', 'xhr-src', $content);
					if (preg_match('/script-src.*?;/i', $content, $scriptMatch) &&  preg_match_all('/(\'unsafe-(?:eval|inline)\')/', $scriptMatch[0], $match))
					{
						$options = Array();
						$script  = $scriptMatch[0];
						foreach ($match[0] as $option)
						{
							$script    = str_replace($option, '', $script);
							$options[] = preg_replace('/\'unsafe-([a-z]+)\'/', '\\1-script', $option);
						}
						$script  = preg_replace('/\s+/', ' ', $script);
						$content = str_replace($scriptMatch[0], $script, $content) . '; ' . (count($options) ? 'options ' . implode(' ', $options) : '');
					}
				}
			}

			//  All versions of Safari and Chrome up to 24 need the X-Webkit-CSP header
			else if (isset($version->webkit) && (!isset($version->chrome) || $version->chrome < 25))
			{
				$header = 'X-Webkit-CSP';
			}
		}
		if (!headers_sent())
			header($header . ': ' . $content);
	}
}
