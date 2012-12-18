<?php


/**
 *  Block Template Feature, handles the <k:block /> feature which allow for repeating blocks of (x)html
 *  @name    ScaffoldSourceBlock
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplateFeatureBlock extends ScaffoldTemplateFeature
{
	protected $_marker;
	protected $_data;
	protected $_stack;


	/**
	 *  Do all preparations needed for the feature to do its deed
	 *  @name   prepare
	 *  @type   method
	 *  @access public
	 *  @return bool success
	 */
	public function prepare()
	{
		$this->_marker = $this->_node->parentNode->insertBefore(
			$this->_getDOMDocument()->createComment('block \'' . $this->name . '\''),
			$this->_node
		);

		$this->_data = '';
		foreach ($this->_node->childNodes as $child)
			$this->_data .= trim($this->_getDOMDocument()->saveXML($child));

		$this->_node->parentNode->removeChild($this->_node);
	}

	/**
	 *  Duplicate the block features content
	 *  @name   duplicate
	 *  @type   method
	 *  @access public
	 *  @return Template object
	 */
	public function duplicate()
	{
		return $this->_addToStack($this->instance('/Template', $this->_data, $this->_template));
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
		$this->_renderStack();
		$this->_marker->parentNode->removeChild($this->_marker);
	}

	/**
	 *  Add the given template to the internal stack of duplicated blocks
	 *  @name   _addToStack
	 *  @type   method
	 *  @access protected
	 *  @param  Template object
	 *  @return Template object
	 *  @note   The given template object will also be prepared with some predefined variables
	 */
	protected function _addToStack($template)
	{
		if (!is_array($this->_stack))
			$this->_stack = Array();

		$this->_stack[] = $this->_getPopulatedTemplate($template, count($this->_stack));
		return $template;
	}

	/**
	 *  Render the internal stack of duplicated blocks
	 *  @name   _renderStack
	 *  @type   method
	 *  @access protected
	 *  @return void
	 */
	protected function _renderStack()
	{
		if (is_array($this->_stack))
			foreach ($this->_stack as $template)
			{
				$dom = $template->render(true, true);
				foreach ($dom->childNodes as $child)
					$this->_marker->parentNode->insertBefore(
						$this->_marker->ownerDocument->importNode($child, true),
						$this->_marker
					);
			}
	}

	/**
	 *  Prefill the given template with default variables
	 *  @name   _getPopulatedTemplate
	 *  @type   method
	 *  @access protected
	 *  @return Template object
	 */
	protected function _getPopulatedTemplate($template, $index)
	{
		$template->{'_position'} = $index;
		$template->{'_parity'}   = $index % 2 == 0 ? 'even' : 'odd';
		$template->{'_name'}     = $this->name;

		return $template;
	}
}