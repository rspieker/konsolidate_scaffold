<?php


/**
 *  Require Template Feature, handles the <k:require /> feature which collects all external requirements such as javascripts and stylesheets
 *  @name    ScaffoldTemplateFeatureRequire
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplateFeatureRequire extends ScaffoldTemplateFeature
{
	/**
	 *  Do all preparations needed for the feature to do its deed
	 *  @name   prepare
	 *  @type   method
	 *  @access public
	 *  @return bool success
	 */
	public function prepare()
	{
		if ($this->attribute('file') && !$this->attribute('type'))
		{
			$type = null;

			switch (pathinfo($this->attribute('file'), PATHINFO_EXTENSION))
			{
				case 'js':
					$type = 'text/javascript';
					break;

				case 'css':
					$type = 'text/css';
					break;
			}

			if (!empty($type))
				$this->attribute('type', $type);
		}
	}
}