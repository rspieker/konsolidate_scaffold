<?php


/**
 *  Basic template feature class, associated with a DOMNode in the (XML) template
 *  @name    ScaffoldTemplateFeature
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplateFeature extends Konsolidate
{
	protected $_node;
	protected $_placeholder;
	protected $_template;
	protected $_variable;


	/**
	 *  The constructor
	 *  @name   __construct
	 *  @type   method
	 *  @access public
	 *  @param  Konsolidate $parent, $node=null, $template=null
	 *  @return
	 */
	public function __construct(Konsolidate $parent, $node=null, $template=null)
	{
		parent::__construct($parent);

		if (!empty($node))
		{
			$this->_node = $node;
			foreach ($this->getAttributes() as $key=>$value)
				$this->{$key} = $value;
		}
		if (!empty($template))
			$this->_template = $template;
	}

	/**
	 *  Do all preparations needed for the feature to do its deed
	 *  @name   prepare
	 *  @type   method
	 *  @access public
	 *  @return bool success
	 */
	public function prepare()
	{
		return true;
	}

	/**
	 *  Render the feature
	 *  @name   render
	 *  @type   method
	 *  @access public
	 *  @return bool success
	 */
	public function render()
	{
		$this->_clean();
		return true;
	}

	/**
	 *  Return the DOMNode value
	 *  @name   value
	 *  @type   method
	 *  @access public
	 *  @return string node value
	 */
	public function value()
	{
		return $this->_node->nodeValue;
	}

	/**
	 *  Return the DOMNode element which represents the feature
	 *  @name   node
	 *  @type   method
	 *  @access public
	 *  @return DOMNode node
	 */
	public function node()
	{
		return $this->_node;
	}

	/**
	 *  Return a DOMNode or DOMText element to be used as offset
	 *  @name   offsetNode
	 *  @type   method
	 *  @access public
	 *  @return DOMNode or DOMText node
	 */
	public function offsetNode()
	{
		return $this->_placeholder ? $this->_placeholder : $this->_node;
	}

	/**
	 *  Get (and/or set) an attribute value
	 *  @name   attribute
	 *  @type   method
	 *  @access public
	 *  @param  string attribute name
	 *  @param  string value (default null, if not null the value is set for the attribute)
	 *  @return string attribute value
	 */
	public function attribute($name, $value=null)
	{
		if (!is_null($value))
			$this->_node->setAttribute($name, $value);
		return $this->_node->hasAttribute($name) ? $this->_node->getAttribute($name) : false;
	}

	/**
	 *  Obtain a key/value array of all the attributes
	 *  @name   getAttributes
	 *  @type   method
	 *  @access public
	 *  @return array attributes
	 */
	public function getAttributes()
	{
		$return = Array();
		foreach ($this->_node->attributes as $attribute)
			$return[$attribute->nodeName] = $attribute->nodeValue;
		return $return;
	}

	/**
	 *  Magic getter, adding a failsafe to look at the node attributes if no local property was found
	 *  @name   __get
	 *  @type   method
	 *  @access public (magic)
	 *  @param  string property
	 *  @return mixed value
	 */
	public function __get($property)
	{
		$return = parent::__get($property);
		if (!$return)
			$return = $this->attribute($property);
		return $return;
	}

	/**
	 *  Obtain the DOMDocument in which the feature DOMNode resides
	 *  @name   _getDOMDocument
	 *  @type   method
	 *  @access protected
	 *  @return DOMDocument
	 */
	protected function _getDOMDocument()
	{
		return $this->_node instanceof DOMNode && $this->_node->ownerDocument ? $this->_node->ownerDocument : false;
	}

	/**
	 *  Remove all evidence the feature was there
	 *  @name   _clean
	 *  @type   method
	 *  @access protected
	 *  @param
	 *  @return
	 */
	protected function _clean()
	{
		if ($this->_node->parentNode)
		{
			$dom = $this->_getDOMDocument();
			$this->_placeholder = $this->_node->parentNode->insertBefore(
				$dom->createTextNode(''),
				$this->_node
			);
			$this->_node->parentNode->removeChild($this->_node);
		}
	}
}