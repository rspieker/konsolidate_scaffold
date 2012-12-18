<?php


/**
 *  Script Template Feature, handles the <k:script /> feature which collects all javascript requirements and removes unwanted duplicates
 *  @name    ScaffoldSourceScript
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplateFeatureScript extends ScaffoldTemplateFeature
{
	/**
	 *  Render the feature
	 *  @name   render
	 *  @type   method
	 *  @access public
	 *  @return bool success
	 */
	public function render()
	{
		$files    = Array();
		$requires = $this->_template->getFeatures('require', Array('type'=>'text/javascript'), true);
		$dom      = $this->_getDOMDocument();

		foreach ($requires as $requirement)
		{
			//  requirements referencing an external file will be included only once unless the multiple="true" attribute is set
			if (isset($requirement->file))
			{
				if (!isset($files[$requirement->file]) || $requirement->multiple == 'true')
					$files[$requirement->file] = true;
				else
					continue;
			}

			//  if the require feature has been fixated (either the template author added an attribute fixate="true" or
			//  the feature class was overruled and it was fixated in the extending class), use the require feature
			//  element as offset, otherwise the current feature element is used as offset (effectively collecting the
			//  elements in one place)
			$offset = $requirement->fixate == 'true' ? $requirement->offsetNode() : $this->_node;

			//  create the script element right before the offset element
			$node = $offset->parentNode->insertBefore(
				$dom->createElement('script'),
				$offset
			);
			$node->setAttribute('type', 'text/javascript');

			//  if the requirement has the file property, we need to reference it differently
			if (isset($requirement->file))
			{
				$node->setAttribute('src', $requirement->file);
				$node->appendChild($dom->createTextNode(''));
			}
			else
			{
				$source = $requirement->value();
				if (!empty($source))
				{
					//  minify the source and append it to the new element
					$source = trim($this->call('/Source/Script/minify', $source), ';');
					$node->appendChild($dom->createCDATASection($source));
				}
			}
		}

		parent::render();
	}
}