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
		if (get_class($this) === __CLASS__)
			$this->_renderDummy();
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

	/**
	 *
	 */
	protected function _renderDummy()
	{
		$dom  = $this->_getDOMDocument();
		$name = $this->_node->localName;
		$info = $this->_node->parentNode->insertBefore(
			$dom->createElement('div'),
			$this->_node
		);
		$info->setAttribute('style', 'border: 1px solid #900; background-color: #fd9; color: #900; margin: 20px; border-radius: 10px;');
		$info->appendChild($dom->createElement('h2', 'Unknown template feature: ' . $name));

		//  describe from where the feature request originated
		$origin = $info->appendChild($dom->createElement('div'));
		$origin->appendChild($dom->createElement('h4', 'Origin'));
		$origin->appendChild($dom->createElement('p', 'The template where the feature was called from is:'));
		$origin->appendChild($dom->createElement('code', $this->_template->origin));
		$origin->appendChild($dom->createElement('p', 'The exact feature syntax is:'));
		$origin->appendChild($dom->createElement('code', $dom->saveXML($this->_node)));

		//  describe where we've looked for the corresponding file
		$search = $info->appendChild($dom->createElement('div'));
		$search->appendChild($dom->createElement('h4', 'Paths'));
		$search->appendChild($dom->createElement('p', 'The feature was not found in the project libraries, expected one of:'));
		$list = $search->appendChild($dom->createElement('ul'));
		foreach ($this->getFilePath() as $tier=>$path)
		{
			$item = $list->appendChild($dom->createElement('li'));
			$item->appendChild($dom->createTextNode('class '));
			$item->appendChild($dom->createElement('strong', $tier . ucFirst($name)));
			$item->appendChild($dom->createTextNode(' in ' . $path . '/' . $name . '.class.php'));
		}

		$create = $info->appendChild($dom->createElement('div'));
		$create->appendChild($dom->createElement('h4', 'Where to go from here'));
		$create->appendChild($dom->createElement('p', 'Assuming the syntax of the feature is correct, you now need to create a feature implementation class, this should be (one of):'));
		$list = $create->appendChild($dom->createElement('ul'));
		foreach ($this->getFilePath() as $tier=>$path)
		{
			if (strpos($tier, 'Scaffold') === 0)
				break;
			$item = $list->appendChild($dom->createElement('li'));
			$item->appendChild($dom->createElement('code', 'class ' . $tier . ucFirst($name) . ' extends ' . __CLASS__));
			$item->appendChild($dom->createTextNode(' in ' . $path . '/' . $name . '.class.php'));
		}

	}
}