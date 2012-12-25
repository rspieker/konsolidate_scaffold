<?php


/**
 *  Template class
 *  @name    ScaffoldTemplate
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplate extends Konsolidate
{
	const PHASE_INIT        = 'PHASE:init';
	const PHASE_PREPARE     = 'PHASE:prepare';
	const PHASE_READY       = 'PHASE:ready';
	const PHASE_REPLACE     = 'PHASE:replace';
	const PHASE_ASSIGN      = 'PHASE:assign';
	const PHASE_PRE_RENDER  = 'PHASE:pre-render';
	const PHASE_RENDER      = 'PHASE:render';

	protected $_entityResolver;
	protected $_phase;
	protected $_content;
	protected $_namespace;
	protected $_xpath;
	protected $_feature;
	protected $_replace;
	protected $_child;
	protected $_hook;
	protected $_filters;

	/**
	 *  Constructor
	 *  @name   __construct
	 *  @type   method
	 *  @access public
	 *  @param  Konsolidate $parent
	 *  @param  mixed source
	 *  @param  ScaffoldTemplate parentTemplate
	 *  @param  bool prepare
	 *  @return ScaffoldTemplate
	 */
	public function __construct(Konsolidate $parent, $source=null, $parentTemplate=null, $prepare=true)
	{
		parent::__construct($parent);

		$this->_entityResolver = $this->get('/Config/template/entityresolver', 'Entity/utf8');
		$this->_namespace      = $this->_getNamespace();
		$this->_feature        = Array();
		$this->_child          = Array();

		$filters = $this->get('/Config/Template/filters', 'comment, whitespace');
		//  if filters are set (read: not explicitly turned off) and this template instance has no parent template, hook the filters to the PHASE_RENDER phase
		if (!empty($filters) && !$parentTemplate)
		{
			$this->_filters = preg_split('/\s*,\s*/', $filters);
			$this->addHook(self::PHASE_RENDER, Array($this, '_applyFilters'));
		}

		if (!empty($source))
			$this->load($source, $parentTemplate, $prepare);
	}

	/**
	 *  Load template data from a DOMDocument, DOMElement, string or file
	 *  @name   load
	 *  @type   method
	 *  @access public
	 *  @param  mixed source
	 *  @param  ScaffoldTemplate parentTemplate
	 *  @param  bool prepare
	 *  @return ScaffoldTemplate
	 */
	public function load($source, $parentTemplate=null, $prepare=true)
	{
		$this->_enterPhase(self::PHASE_INIT);

		$data = null;

		if ($source instanceof DOMDocument)
		{
			$data = $source;
		}
		else if ($source instanceof DOMElement)
		{
			$data = new DOMDocument();
			$data->appendChild($data->importNode($source, true));
		}
		else if (is_string($source))
		{
			$data   = new DOMDocument();
			$file   = $this->_getFileName($source);
			$source = $this->_wrapSource($file ? file_get_contents($file) : $source);
			$data->loadXML($source);
		}
		else
		{
			$this->exception('Cannot load template from (' . gettype($source) . ')' . var_export($source, true));
		}

		if ($data instanceof DOMDocument)
		{
			foreach ($this->_namespace as $namespace=>$path)
				$data->createAttributeNS($path, $namespace . ':' . get_class($this));

			$this->_content = $data;
			$this->_xpath   = new DOMXPath($this->_content);

			if ($prepare)
				$this->prepare();
		}

		if ($parentTemplate instanceof self)
			$parentTemplate->addChild($this);

		$this->_enterPhase(self::PHASE_READY);
		return $this;
	}

	/**
	 *  Enter the preparation phase and start extracting (unprocessed) template features
	 *  @name   prepare
	 *  @type   method
	 *  @access public
	 *  @return void
	 */
	public function prepare()
	{
		$this->_enterPhase(self::PHASE_PREPARE);
		$this->_extractFeatures();
	}

	/**
	 *  Register a child template, mainly for feature inheritance
	 *  @name   addChild
	 *  @type   method
	 *  @access public
	 *  @param  ScaffoldTemplate template
	 *  @return
	 */
	public function addChild(ScaffoldTemplate $template)
	{
		$this->_child[] = $template;
		return $template;
	}

	/**
	 *  Obtain features by name, optionally filtered on the feature node attributes
	 *  @name   getFeatures
	 *  @type   method
	 *  @access public
	 *  @param  string type
	 *  @param  array  filter
	 *  @return array  matching features
	 *  @note   Usage: <k:whatever name="example1" /><k:whatever name="example2" />
	 *          Array(2) [template object]->getFeatures('whatever');
	 *          Array(1) [template object]->getFeatures('whatever', Array('name'=>'example2'));
	 */
	public function getFeatures($type, Array $filter=null, $includeChildTemplates=false)
	{
		$list = $this->_getFeaturesByType($type);
		if (is_array($list) && is_array($filter) && count($filter))
		{
			$matches = Array();
			foreach ($list as $feature)
			{
				$match = true;
				foreach ($filter as $key=>$value)
					if ($feature->attribute($key) != $value)
					{
						$match = false;
						break;
					}

				if ($match)
					$matches[] = $feature;
			}
			$list = $matches;
		}

		if ($includeChildTemplates)
			foreach ($this->_child as $template)
			{
				$features = $template->getFeatures($type, $filter, $includeChildTemplates);
				if (count($features))
					$list = array_merge($list, $features);
			}

		return $list;
	}

	/**
	 *  Duplicate the contents of a block-feature (<k:block name="xx">)
	 *  @name   block
	 *  @type   method
	 *  @access public
	 *  @param  string name
	 *  @return mixed ScaffoldTemplate or ScaffoldTemplateGroup
	 */
	public function block($name)
	{
		$list = $this->getFeatures('block', Array('name'=>$name));

		if (count($list) == 1)
		{
			return $list[0]->duplicate();
		}
		else if (count($list) > 1)
		{
			$group = Array();
			foreach ($list as $member)
				$group[] = $member->duplicate();
			$group = $this->instance('Group', $group);
			return $group;
		}
		return false;
	}

	/**
	 *  Obtain the current state of the internal DOM template
	 *  @name   getDOM
	 *  @type   method
	 *  @access public
	 *  @return DOMDocument
	 */
	public function getDOM()
	{
		return $this->_content;
	}

	/**
	 *  Render the template, including all features and optionally replace the placeholders
	 *  @name   render
	 *  @type   method
	 *  @access public
	 *  @param  bool replace (default true)
	 *  @param  bool asDOM (default false)
	 *  @return mixed string HTML or DOMDocument
	 *  @note   this method will trigger (in order): PHASE_REPLACE (if bool replace is true), PHASE_PRE_RENDER and PHASE_RENDER
	 */
	public function render($replace=true, $asDOM=false)
	{
		if ($replace)
		{
			$this->_enterPhase(self::PHASE_REPLACE);
			$this->_replace();
		}
		$this->_enterPhase(self::PHASE_PRE_RENDER);
		$this->_render();

		//  create a reference to the inner DOMDocument
		$dom = $this->_content;

		//  restore the natural balance of the DOMDocument, as we (may) have wrecked havoc by splitting textnodes and manipulating removing feature nodes
		$dom->normalizeDocument();
		$this->_enterPhase(self::PHASE_RENDER);

		//  This works arounds PHP bug #40359 (https://bugs.php.net/bug.php?id=40359) where the saveHTML method will actually meddle with the whitespace in the output, so we create the HTML output ourselves
		if (LIBXML_VERSION > 20632)
		{
			$selfClosing = Array('base', 'basefont', 'frame', 'link', 'meta', 'area', 'br', 'col', 'hr', 'img', 'input', 'param');
			//  find all nodes which do not contain any comment, text, CDATA or element node
			foreach ($this->_xpath->query('//*[not(node())]') as $node)
				if (!in_array(strToLower($node->nodeName), $selfClosing))
					$node->appendChild($this->_content->createTextNode(''));

			return $asDOM ? $dom : $dom->saveXML($dom->doctype) . PHP_EOL . PHP_EOL . $dom->saveXML($dom->documentElement);
		}
		return $asDOM ? $dom : $dom->saveHTML();
	}

	/**
	 *  Register a hook callback
	 *  @name   addHook
	 *  @type   method
	 *  @access public
	 *  @param  string phase (use the predefined constants)
	 *  @param  mixed callback (a valid PHP callback, either a string functionName or an Array(object, functionName)
	 *  @return
	 */
	public function addHook($phase, $callback)
	{
		if (!is_array($this->_hook))
			$this->_hook = Array();
		if (!isset($this->_hook[$phase]))
			$this->_hook[$phase] = Array();
		$this->_hook[$phase][] = $callback;

		//  if we are adding a callback to the current phase, we need to trigger the new phase callback immediately
		if ($phase === $this->_phase)
			$this->_triggerPhaseCallback($this->_phase, $callback);

		return $this;
	}

	/**
	 *  Return all features of a certain type
	 *  @name   _getFeaturesByType
	 *  @type   method
	 *  @access protected
	 *  @param  string type
	 *  @return array feature node
	 */
	protected function _getFeaturesByType($type)
	{
		return array_key_exists($type, $this->_feature) ? $this->_feature[$type] : Array();
	}

	/**
	 *  Replace all placeholders in the DOMText elements with their (set or default) values
	 *  @name   _replace
	 *  @type   method
	 *  @access protected
	 *  @return void
	 */
	protected function _replace()
	{
		//  loop through all attributes and textnodes which contain one or more placeholders
		foreach ($this->_xpath->query('//text()[not(ancestor::script) and contains(.,"{") and contains(.,"}")]|//@*[not(ancestor::script) and contains(.,"{") and contains(.,"}")]') as $node)
		{
			//  extract the placeholders
			if (preg_match_all('/\{([a-zA-Z0-9\_-]+)(?:\:(.*))?\}/U', $node->nodeValue, $match) && count($match) >= 3)
			{
				//  if the node is an attribute element, we rather deal with the DOMText inside it
				if ($node instanceof DOMAttr)
					$node = $node->firstChild;

				for ($i = 0; $i < count($match[0]); ++$i) //  and replace them
				{
					$value = $this->_placeholderValue($match[1][$i], $match[2][$i], $node);
					$start = strpos($node->nodeValue, $match[0][$i]);

					if ($start > 0)
						$node = $node->splitText($start);
					//  at this point the node will at least start with our placeholder pattern, in the next step the
					//  reference to node may (or may not) be changed to the remainder after the split, hence we are
					//  safe to reference the current state
					$replace = $node;
					if (strlen($match[0][$i]) < strlen($node->nodeValue))
						$node = $node->splitText(strlen($match[0][$i]));

					//  if the value is a DOMNode, we can always safely replace it
					if ($value instanceof DOMNode)
					{
						$replace->parentNode->insertBefore(
							$replace->ownerDocument->importNode($value, true),
							$replace
						);
						$replace->parentNode->removeChild($replace);
					}
					else if (is_scalar($value))
					{
						$replace->nodeValue = $value;
					}
				}

				//  if there was a placeholder in an attribute value and that value is now empty, remove the entire attribute
				if ($node->parentNode instanceof DOMAttr && preg_match('/^\s*$/', $node->parentNode->nodeValue))
					$node->parentNode->parentNode->removeAttributeNode($node->parentNode);
			}
		}
	}

	/**
	 *  Remove any wrapping applied earlier and trigger the feature rendering
	 *  @name   _render
	 *  @type   method
	 *  @access protected
	 *  @return void
	 */
	protected function _render()
	{
		//  if we are dealing with a wrapped document, it's time to give up meddling
		if ($this->_content && $this->_content->documentElement->nodeName == $this->_getClassName())
		{
			//  move all elements inside the current documentElement (our wrapping element) into the document itself
			while ($this->_content->documentElement->firstChild)
				$this->_content->appendChild($this->_content->documentElement->removeChild($this->_content->documentElement->firstChild));
			//  remove the wrapping element
			$this->_content->removeChild($this->_content->documentElement);
		}

		//  render all features
		foreach ($this->_feature as $name=>$instances)
			foreach ($instances as $instance)
				$instance->render();

		//  remove any custom namespace we have inserted during template load
		foreach ($this->_namespace as $local=>$uri)
			$this->_content->documentElement->removeAttributeNS($uri, $local);
	}

	/**
	 *  Obtain the proper value for given placeholder
	 *  @name   _placeholderValue
	 *  @type   method
	 *  @access protected
	 *  @param  string key
	 *  @param  mixed  default (one of string, number, DOMText, DOMElement, DOMDocument)
	 *  @return mixed replacement value (string, number, DOMElement)
	 */
	protected function _placeholderValue($key, $default=null, DOMNode $node=null)
	{
		$value = isset($this->_property[$key]) ? $this->_property[$key] : $default;

		if (!is_string($value) && !is_numeric($value))
		{
			if ($value instanceof self)
			{
				$value = $value->render(true, true);
			}

			if ($value instanceof DOMText)
			{
				$value = $value->nodeValue;
			}
			else if ($value instanceof DOMElement)
			{
				$value = $value;
			}
			else if ($value instanceof DOMDocument)
			{
				$value = $value->documentElement;
			}
			else
			{
				$this->call('/Log/message', 'Cannot handle ' . (is_object($value) ? get_class($value) : gettype($value)) . ' placeholder values', 2);
			}
		}
		return $value;
	}

	/**
	 *  Extract all the features
	 *  @name   _extractFeatures
	 *  @type   method
	 *  @access protected
	 *  @return
	 */
	protected function _extractFeatures()
	{
		if ($this->_content instanceof DOMDocument)
		{
			$query = '';
			foreach ($this->_namespace as $ns=>$path)
				$query .= (!empty($query) ? '|' : '') . '//*[not(ancestor::k:*) and starts-with(name(),"' . $ns . ':")]|//@*[not(ancestor::k:*) and starts-with(name(),"' . $ns . ':")]';

			if (!empty($query))
				foreach ($this->_xpath->query($query) as $instruct)
					if ($instruct->parentNode) //  verify whether the feature is in the DOM
						$this->_instanceFeature($instruct);
		}
	}

	/**
	 *  _instanceFeature
	 *  @name   _instanceFeature
	 *  @type   method
	 *  @access protected
	 *  @param  $node
	 *  @return
	 */
	protected function _instanceFeature($node)
	{
		$localName = $node->localName;
		if (!isset($this->_feature[$localName]))
			$this->_feature[$localName] = Array();

		if (!$this->_featureIsProcessed($node))
		{
			$type     = $this->checkModuleAvailability('Feature/' . $localName) ? 'Feature/' . $localName : 'Feature';
			$instance = $this->instance($type, $node, $this);
			$instance->prepare();
			$this->_feature[$node->localName][] = $instance;

			if (substr(get_class($instance), -15) == 'TemplateFeature')
				$this->call('/Log/message', 'Feature not found: "' . $node->localName . '", using the default feature class "' . get_class($instance) . '" instead.', 2);
		}
	}

	/**
	 *  _featureIsProcessed
	 *  @name   _featureIsProcessed
	 *  @type   method
	 *  @access protected
	 *  @param  $node
	 *  @return
	 */
	protected function _featureIsProcessed($node)
	{
		if (isset($this->_feature[$node->localName]))
			foreach ($this->_feature[$node->localName] as $feature)
				if ($node->isSameNode($feature->node()))
					return true;
		return false;
	}

	/**
	 *  _enterPhase
	 *  @name   _enterPhase
	 *  @type   method
	 *  @access protected
	 *  @param  $phase, Array $param=null
	 *  @return
	 */
	protected function _enterPhase($phase, Array $param=null)
	{
		$this->_phase = $phase;
		if (is_array($this->_hook) && isset($this->_hook[$phase]) && is_array($this->_hook[$phase]))
			foreach ($this->_hook[$phase] as $call)
				$this->_triggerPhaseCallback($this->_phase, $call, $param);
	}

	/**
	 *  _triggerPhaseCallback
	 *  @name   _triggerPhaseCallback
	 *  @type   method
	 *  @access protected
	 *  @param  $phase, Array $param=null
	 *  @return
	 */
	protected function _triggerPhaseCallback($phase, $call, Array $param=null)
	{
		$argument = Array(
			'type'     => $phase,
			'dom'      => $this->_content,
			'xpath'    => new DOMXPath($this->_content),
			'template' => $this
		);
		if (is_array($param))
			$argument = array_merge($param, $argument);

		return call_user_func_array($call, Array((object) $argument));
	}

	/**
	 *  Wrap the XML input string with a generic element which can easily be replaced when processing, this actually
	 *  does preserve the DOMDocumentType (<!DOCTYPE *>)
	 *  @name   _wrapSource
	 *  @type   method
	 *  @access protected
	 *  @param  string xml source
	 *  @return string xml source
	 */
	protected function _wrapSource($source)
	{
		//  remove xml declaration
		$source  = preg_replace('/\<\?.*\?\>/', '', $source);
		$doctype = $this->_getDocType($source);
		$class   = $this->_getClassName();
		$ns      = Array();

		foreach ($this->_namespace as $namespace=>$path)
			$ns[] = 'xmlns:' . $namespace . '="' . $path . '"';

		if ($this->_entityResolver && preg_match_all('/&([a-zA-Z]+);/U', $source, $match))
			for ($i = 0; $i < count($match[1]); ++$i)
				$source = str_replace($match[0][$i], $this->call($this->_entityResolver, $match[1][$i]), $source);

		return $doctype . '<' . $class . (count($ns) ? ' ' . implode(' ', $ns) : '') . '>' . str_replace($doctype, '', $source) . '</' . $class . '>';
	}

	/**
	 *  Get the DOMDocumentType (<!DOCTYPE *>) from given XML string
	 *  @name   _getDoctype
	 *  @type   method
	 *  @access protected
	 *  @param  string xml
	 *  @return string doctype
	 */
	protected function _getDoctype($xml)
	{
		$return = null;
		if (is_string($xml))
		{
			if (preg_match('/(<![a-zA-Z]+[\s\S]+\[[\s\S]*\]>)/U', $xml, $match) || preg_match('/(<![a-zA-Z]+[\s\S]*>)/U', $xml, $match))
				$return = $match[0];
		}
		return $return;
	}

	/**
	 *  Obtain the classname of the current object, always ready to be used as valid xml nodename
	 *  @name   _getClassName
	 *  @type   method
	 *  @access protected
	 *  @return string class name
	 */
	protected function _getClassName()
	{
		return preg_replace('/[^a-zA-Z]/', '', get_class($this));
	}


	/**
	 *  Try to determine if given source may be a file and if so, see whether it exists
	 *  @name   _getFileName
	 *  @type   method
	 *  @access protected
	 *  @param  string source
	 *  @return string filename (false on error)
	 */
	protected function _getFileName($source)
	{
		$return = false;
		if (preg_match('/^[a-zA-Z0-9_\.\/-]+\.[a-zA-Z]+ml$/', $source))
		{
			if (realpath($source))
				$return = realpath($source);
			else if (realpath($this->get('/Config/Template/path') . '/' . $source))
				$return = realpath($this->get('/Config/Template/path') . '/' . $source);
			else if (defined('DOCUMENT_ROOT') && realpath(DOCUMENT_ROOT . '/' . $source))
				$return = realpath(DOCUMENT_ROOT . '/' . $source);
		}
		return $return;
	}

	/**
	 *  Obtain the namespaces which should be used for features
	 *  @name   _getNamespace
	 *  @type   method
	 *  @access protected
	 *  @return array namespaces
	 */
	protected function _getNamespace()
	{
		$namespace = Array('k' => '/');

		return $namespace;
	}

	/**
	 *  Magic setter to trigger PHASE_ASSIGN phase hooks and setting the value
	 *  @name   __set
	 *  @type   method
	 *  @access public
	 *  @return void
	 */
	public function __set($property, $value)
	{
		$this->_enterPhase(self::PHASE_ASSIGN, Array('property'=>&$property, 'value'=>&$value));
		parent::__set($property, $value);
	}

	/**
	 *  Apply the configured filters
	 *  @name   _applyFilters
	 *  @type   method
	 *  @access protected
	 *  @param  stdClass hook
	 *  @return void
	 */
	protected function _applyFilters($hook)
	{
		if (!$this->_template)
			foreach ($this->_filters as $method)
				$hook->template->call('Filter/' . $method, $hook->dom);
	}
}
