<?php


/**
 *  External source file manipulation
 *  @name    ScaffoldSource
 *  @package Scaffold
 *  @author  Rogier Spieker <rogier@konfirm.net>
 */
class ScaffoldSource extends Konsolidate
{
	protected $_cachePath;

	/**
	 *  Constructor
	 *  @name   __construct
	 *  @type   method
	 *  @access public
	 *  @param  Konsolidate object
	 *  @return ScaffoldSource object
	 */
	public function __construct(Konsolidate $parent)
	{
		parent::__construct($parent);

		$cache = $this->get('/Config/Source/cache', '/tmp');
		if (!realpath($cache))
			$cache = $this->_locatePath($cache);

		$this->_cachePath = realpath($cache);
	}

	/**
	 *  Minify the given source file
	 *  @name   minify
	 *  @type   method
	 *  @access public
	 *  @param  string source
	 *  @return string minified filename (false on error)
	 */
	public function minify($file)
	{
		$file   = $this->_locatePath($file);
		$helper = $this->_getHelper($file);

		if ($file && $helper && method_exists($helper, 'minify'))
		{
			$ext = pathinfo($file, PATHINFO_EXTENSION);
			if (strstr($file, 'min.' . $ext))
				return $file;

			$cacheFile = $this->_cachePath . '/' . substr(basename($file), 0, -strlen($ext)) . 'min.' . $ext;
			if ((realpath($cacheFile) && filemtime($file) < filemtime($cacheFile)) || file_put_contents($cacheFile, $helper->minify(file_get_contents($file))))
				return $cacheFile;
		}
		return false;
	}

	/**
	 *  Create a cache file from the given list of files and return the cache file name
	 *  @name   combine
	 *  @type   method
	 *  @access public
	 *  @param  array file list
	 *  @return string cache file name
	 */
	public function combine($fileList)
	{
		$alphabet = array_map('chr', array_merge(
			range(48, 57),  //  numbers
			range(97, 122), //  lower case characters
			range(65, 90)   //  upper case characters
		));
		$checksum  = explode(PHP_EOL, wordwrap(md5(json_encode($fileList)), 2, PHP_EOL, true));
		$cacheFile = '';
		while (count($checksum))
			$cacheFile .= $alphabet[hexdec(array_shift($checksum)) % count($alphabet)];
		$cacheFile = $this->_cachePath . '/' . $cacheFile . '.' . pathinfo($fileList[0], PATHINFO_EXTENSION);

		if (realpath($cacheFile))
			return basename($cacheFile);

		$fp = fopen($cacheFile, 'w+');
		if ($fp)
		{
			foreach ($fileList as $file)
			{
				$source = trim(file_get_contents($file)) . PHP_EOL;
				if (substr($file, 0, strlen($this->_cachePath)) == $this->_cachePath)
					$source = '/* ' . basename($file) . ' */' . PHP_EOL . $source;
				fputs($fp, $source);
			}
			return basename($cacheFile);
		}
		return false;
	}

	/**
	 *  Get the helper module for given file (based on extension)
	 *  @name   _getHelper
	 *  @type   method
	 *  @access protected
	 *  @param  string file name
	 *  @return ScaffoldSourceScript or ScaffolSourceStyle object
	 *  @note   this method was written just before christmas, hence the name refers to Santa's little helper
	 */
	protected function _getHelper($file)
	{
		$helper = false;
		switch (pathinfo($file, PATHINFO_EXTENSION))
		{
			case 'js':
				$helper = $this->get('Script');
				break;

			case 'css':
				$helper = $this->get('Style');
				break;
		}

		return $helper;
	}


	/**
	 *  Find the real path of given file
	 *  @name   _locatePath
	 *  @type   method
	 *  @access protected
	 *  @param  string filename
	 *  @return string real path
	 */
	protected function _locatePath($path)
	{
		$root   = $this->call('/Tool/documentRoot');
		$rel    = $this->call('/Tool/documentPath');
		$return = false;

		if (realpath($root . '/' . trim($path, '/')))
			$return = realpath($root . '/' . trim($path, '/'));
		else if (realpath($rel . '/' . trim($path, '/')))
			$return = realpath($rel . '/' . trim($path, '/'));

		return $return;
	}
}
