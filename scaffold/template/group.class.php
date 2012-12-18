<?php


/**
 *  Group together features returned by ScaffoldTemplate::getFeatures and bridge all template calls to each member of the group
 *  @name    ScaffoldTemplateGroup
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldTemplateGroup extends Konsolidate
{
	protected $_group;


	/**
	 *  Constructor
	 *  @name   __construct
	 *  @type   method
	 *  @access public
	 *  @param  Konsolidate $parent
	 *  @param  Array feature group
	 *  @return ScaffoldTemplateGroup object
	 */
	public function __construct(Konsolidate $parent, Array $group=null)
	{
		parent::__construct($parent);

		$this->_group = $group;
	}

	/**
	 *  Magic __call method, bridging all undeclared methods to each feature in the group
	 *  @name   __call
	 *  @type   method
	 *  @access public
	 *  @param  string method
	 *  @param  mixed argument
	 *  @return void
	 */
	public function __call($method, $argument)
	{
		foreach ($this->_group as $member)
			call_user_func_array(Array($member, $method), $argument);
	}

	/**
	 *  Implement the template's block method and return a new group
	 *  @name   block
	 *  @type   method
	 *  @access public
	 *  @param  string name
	 *  @return ScaffoldTemplateGroup object
	 */
	public function block($name)
	{
		$return = Array();
		foreach ($this->_group as $member)
			$return[] = $member->block($name);
		return count($return) > 0 ? $this->instance('../Group', $return) : false;
	}

	/**
	 *  Magic setter, setting the given property to each feature in the group
	 *  @name   __set
	 *  @type   method
	 *  @access public
	 *  @param  string property
	 *  @param  mixed value
	 *  @return
	 */
	public function __set($property, $value)
	{
		foreach ($this->_group as $member)
			$member->{$property} = $value;
	}


	/**
	 *  Magic getter, returning the first occurrence of the requirested property
	 *  @name   __get
	 *  @type   method
	 *  @access public
	 *  @param  string property
	 *  @return mixed value
	 */
	public function __get($property)
	{
		foreach ($this->_group as $member)
			if (isset($member->{$property}))
				return $member->{$property};
	}
}
