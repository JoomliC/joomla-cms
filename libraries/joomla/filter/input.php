<?php
/**
 * @package     Joomla.Platform
 * @subpackage  Filter
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

defined('JPATH_PLATFORM') or die;

use Joomla\String\StringHelper;

/**
 * JFilterInput is a class for filtering input from any data source
 *
 * Forked from the php input filter library by: Daniel Morris <dan@rootcube.com>
 * Original Contributors: Gianpaolo Racca, Ghislain Picard, Marco Wandschneider, Chris Tobin and Andrew Eddie.
 *
 * @since  11.1
 */
class JFilterInput
{
	/**
	 * A container for JFilterInput instances.
	 *
	 * @var    array
	 * @since  11.3
	 */
	protected static $instances = array();

	/**
	 * The array of permitted tags (white list).
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $tagsArray;

	/**
	 * The array of permitted tag attributes (white list).
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $attrArray;

	/**
	 * The method for sanitising tags: WhiteList method = 0 (default), BlackList method = 1
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $tagsMethod;

	/**
	 * The method for sanitising attributes: WhiteList method = 0 (default), BlackList method = 1
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $attrMethod;

	/**
	 * A flag for XSS checks. Only auto clean essentials = 0, Allow clean blacklisted tags/attr = 1
	 *
	 * @var    integer
	 * @since  11.1
	 */
	public $xssAuto;

	/**
	 * A flag for Unicode Supplementary Characters (4-byte Unicode character) stripping.
	 *
	 * @var    integer
	 * @since  CMS 3.5.0
	 */
	public $stripUSC = 0;

	/**
	 * The list of the default blacklisted tags.
	 *
	 * @var    array
	 * @since  11.1
	 */
	public $tagBlacklist = array(
		'applet',
		'body',
		'bgsound',
		'base',
		'basefont',
		'embed',
		'frame',
		'frameset',
		'head',
		'html',
		'id',
		'iframe',
		'ilayer',
		'layer',
		'link',
		'meta',
		'name',
		'object',
		'script',
		'style',
		'title',
		'xml',
	);

	/**
	 * The list of the default blacklisted tag attributes. All event handlers implicit.
	 *
	 * @var    array
	 * @since   11.1
	 */
	public $attrBlacklist = array(
		'action',
		'background',
		'codebase',
		'dynsrc',
		'lowsrc',
	);

	/**
	 * Constructor for inputFilter class. Only first parameter is required.
	 *
	 * @param   array    $tagsArray   List of user-defined tags
	 * @param   array    $attrArray   List of user-defined attributes
	 * @param   integer  $tagsMethod  WhiteList method = 0, BlackList method = 1
	 * @param   integer  $attrMethod  WhiteList method = 0, BlackList method = 1
	 * @param   integer  $xssAuto     Only auto clean essentials = 0, Allow clean blacklisted tags/attr = 1
	 * @param   integer  $stripUSC    Strip 4-byte unicode characters = 1, no strip = 0, ask the database driver = -1
	 *
	 * @since   11.1
	 */
	public function __construct($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1, $stripUSC = -1)
	{
		// Make sure user defined arrays are in lowercase
		$tagsArray = array_map('strtolower', (array) $tagsArray);
		$attrArray = array_map('strtolower', (array) $attrArray);

		// Assign member variables
		$this->tagsArray = $tagsArray;
		$this->attrArray = $attrArray;
		$this->tagsMethod = $tagsMethod;
		$this->attrMethod = $attrMethod;
		$this->xssAuto = $xssAuto;
		$this->stripUSC = $stripUSC;

		/**
		 * If Unicode Supplementary Characters stripping is not set we have to check with the database driver. If the
		 * driver does not support USCs (i.e. there is no utf8mb4 support) we will enable USC stripping.
		 */
		if ($this->stripUSC == -1)
		{
			try
			{
				// Get the database driver
				$db = JFactory::getDbo();

				// This trick is required to let the driver determine the utf-8 multibyte support
				$db->connect();

				// And now we can decide if we should strip USCs
				$this->stripUSC = $db->hasUTF8mb4Support() ? 0 : 1;
			}
			catch (Exception $e)
			{
				// Could not connect to MySQL. Strip USC to be on the safe side.
				$this->stripUSC = 1;
			}
		}
	}

	/**
	 * Returns an input filter object, only creating it if it doesn't already exist.
	 *
	 * @param   array    $tagsArray   List of user-defined tags
	 * @param   array    $attrArray   List of user-defined attributes
	 * @param   integer  $tagsMethod  WhiteList method = 0, BlackList method = 1
	 * @param   integer  $attrMethod  WhiteList method = 0, BlackList method = 1
	 * @param   integer  $xssAuto     Only auto clean essentials = 0, Allow clean blacklisted tags/attr = 1
	 * @param   integer  $stripUSC    Strip 4-byte unicode characters = 1, no strip = 0, ask the database driver = -1
	 *
	 * @return  JFilterInput  The JFilterInput object.
	 *
	 * @since   11.1
	 */
	public static function &getInstance($tagsArray = array(), $attrArray = array(), $tagsMethod = 0, $attrMethod = 0, $xssAuto = 1, $stripUSC = -1)
	{
		$sig = md5(serialize(array($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto)));

		if (empty(self::$instances[$sig]))
		{
			self::$instances[$sig] = new JFilterInput($tagsArray, $attrArray, $tagsMethod, $attrMethod, $xssAuto, $stripUSC);
		}

		return self::$instances[$sig];
	}

	/**
	 * Method to be called by another php script. Processes for XSS and
	 * specified bad code.
	 *
	 * @param   mixed   $source  Input string/array-of-string to be 'cleaned'
	 * @param   string  $type    The return type for the variable:
	 *                           INT:       An integer, or an array of integers,
	 *                           UINT:      An unsigned integer, or an array of unsigned integers,
	 *                           FLOAT:     A floating point number, or an array of floating point numbers,
	 *                           BOOLEAN:   A boolean value,
	 *                           WORD:      A string containing A-Z or underscores only (not case sensitive),
	 *                           ALNUM:     A string containing A-Z or 0-9 only (not case sensitive),
	 *                           CMD:       A string containing A-Z, 0-9, underscores, periods or hyphens (not case sensitive),
	 *                           BASE64:    A string containing A-Z, 0-9, forward slashes, plus or equals (not case sensitive),
	 *                           STRING:    A fully decoded and sanitised string (default),
	 *                           HTML:      A sanitised string,
	 *                           ARRAY:     An array,
<<<<<<< HEAD
	 *                           PATH:      A sanitised file path,
=======
	 *                           PATH:      A sanitised file path, or an array of sanitised file paths,
>>>>>>> joomla/staging
	 *                           TRIM:      A string trimmed from normal, non-breaking and multibyte spaces
	 *                           USERNAME:  Do not use (use an application specific filter),
	 *                           RAW:       The raw string is returned with no filtering,
	 *                           unknown:   An unknown filter will act like STRING. If the input is an array it will return an
	 *                                      array of fully decoded and sanitised strings.
	 *
	 * @return  mixed  'Cleaned' version of input parameter
	 *
	 * @since   11.1
	 */
	public function clean($source, $type = 'string')
	{
		// Strip Unicode Supplementary Characters when requested to do so
		if ($this->stripUSC)
		{
			// Alternatively: preg_replace('/[\x{10000}-\x{10FFFF}]/u', "\xE2\xAF\x91", $source) but it'd be slower.
			$source = $this->stripUSC($source);
		}

<<<<<<< HEAD
		// Handle the type constraint
=======
		// Handle the type constraint cases
>>>>>>> joomla/staging
		switch (strtoupper($type))
		{
			case 'INT':
			case 'INTEGER':
				$pattern = '/[-+]?[0-9]+/';

				if (is_array($source))
				{
					$result = array();

					// Itterate through the array
					foreach ($source as $eachString)
					{
						preg_match($pattern, (string) $eachString, $matches);
						$result[] = isset($matches[0]) ? (int) $matches[0] : 0;
					}
				}
				else
				{
					preg_match($pattern, (string) $source, $matches);
					$result = isset($matches[0]) ? (int) $matches[0] : 0;
				}

				break;

			case 'UINT':
				$pattern = '/[-+]?[0-9]+/';

				if (is_array($source))
				{
					$result = array();

					// Itterate through the array
					foreach ($source as $eachString)
					{
						preg_match($pattern, (string) $eachString, $matches);
						$result[] = isset($matches[0]) ? abs((int) $matches[0]) : 0;
					}
				}
				else
				{
					preg_match($pattern, (string) $source, $matches);
					$result = isset($matches[0]) ? abs((int) $matches[0]) : 0;
				}

				break;

			case 'FLOAT':
			case 'DOUBLE':
				$pattern = '/[-+]?[0-9]+(\.[0-9]+)?([eE][-+]?[0-9]+)?/';

				if (is_array($source))
				{
					$result = array();

					// Itterate through the array
					foreach ($source as $eachString)
					{
						preg_match($pattern, (string) $eachString, $matches);
						$result[] = isset($matches[0]) ? (float) $matches[0] : 0;
					}
				}
				else
				{
					preg_match($pattern, (string) $source, $matches);
					$result = isset($matches[0]) ? (float) $matches[0] : 0;
				}

				break;

			case 'BOOL':
			case 'BOOLEAN':
				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$result[] = (bool) $eachString;
					}
				}
				else
				{
					$result = (bool) $source;
				}

				break;

			case 'WORD':
				$pattern = '/[^A-Z_]/i';

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$result[] = (string) preg_replace($pattern, '', $eachString);
					}
				}
				else
				{
					$result = (string) preg_replace($pattern, '', $source);
				}

				break;

			case 'ALNUM':
				$pattern = '/[^A-Z0-9]/i';

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$result[] = (string) preg_replace($pattern, '', $eachString);
					}
				}
				else
				{
					$result = (string) preg_replace($pattern, '', $source);
				}

				break;

			case 'CMD':
				$pattern = '/[^A-Z0-9_\.-]/i';

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$cleaned  = (string) preg_replace($pattern, '', $eachString);
						$result[] = ltrim($cleaned, '.');
					}
				}
				else
				{
					$result = (string) preg_replace($pattern, '', $source);
					$result = ltrim($result, '.');
				}

				break;

			case 'BASE64':
				$pattern = '/[^A-Z0-9\/+=]/i';

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$result[] = (string) preg_replace($pattern, '', $eachString);
					}
				}
				else
				{
					$result = (string) preg_replace($pattern, '', $source);
				}

				break;

			case 'STRING':

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$result[] = (string) $this->remove($this->decode((string) $eachString));
					}
				}
				else
				{
					$result = (string) $this->remove($this->decode((string) $source));
				}

				break;

			case 'HTML':

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$result[] = (string) $this->remove((string) $eachString);
					}
				}
				else
				{
					$result = (string) $this->remove((string) $source);
				}

				break;

			case 'ARRAY':
				$result = (array) $source;
				break;

			case 'PATH':
				$pattern = '/^[A-Za-z0-9_\/-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
<<<<<<< HEAD
				preg_match($pattern, (string) $source, $matches);
				$result = @ (string) $matches[0];
=======

				if (is_array($source))
				{
					$result = array();

					// Itterate through the array
					foreach ($source as $eachString)
					{
						preg_match($pattern, (string) $eachString, $matches);
						$result[] = isset($matches[0]) ? (string) $matches[0] : '';
					}
				}
				else
				{
					preg_match($pattern, $source, $matches);
					$result = isset($matches[0]) ? (string) $matches[0] : '';
				}

				break;

			case 'TRIM':

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$cleaned  = (string) trim($eachString);
						$cleaned  = StringHelper::trim($cleaned, chr(0xE3) . chr(0x80) . chr(0x80));
						$result[] = StringHelper::trim($cleaned, chr(0xC2) . chr(0xA0));
					}
				}
				else
				{
					$result = (string) trim($source);
					$result = StringHelper::trim($result, chr(0xE3) . chr(0x80) . chr(0x80));
					$result = StringHelper::trim($result, chr(0xC2) . chr(0xA0));
				}

>>>>>>> joomla/staging
				break;

			case 'TRIM':
				$result = (string) trim($source);
				$result = JString::trim($result, chr(0xE3) . chr(0x80) . chr(0x80));
				$result = JString::trim($result, chr(0xC2) . chr(0xA0));
				break;

			case 'USERNAME':
				$pattern = '/[\x00-\x1F\x7F<>"\'%&]/';

				if (is_array($source))
				{
					$result = array();

					// Iterate through the array
					foreach ($source as $eachString)
					{
						$result[] = (string) preg_replace($pattern, '', $eachString);
					}
				}
				else
				{
					$result = (string) preg_replace($pattern, '', $source);
				}

				break;

			case 'RAW':
				$result = $source;
				break;

			default:
				// Are we dealing with an array?
				if (is_array($source))
				{
					foreach ($source as $key => $value)
					{
						// Filter element for XSS and other 'bad' code etc.
						if (is_string($value))
						{
							$source[$key] = $this->_remove($this->_decode($value));
						}
					}

					$result = $source;
				}
				else
				{
					// Or a string?
					if (is_string($source) && !empty($source))
					{
						// Filter source for XSS and other 'bad' code etc.
						$result = $this->_remove($this->_decode($source));
					}
					else
					{
						// Not an array or string... return the passed parameter
						$result = $source;
					}
				}
				break;
		}

		return $result;
	}

	/**
	 * Function to punyencode utf8 mail when saving content
	 *
	 * @param   string  $text  The strings to encode
	 *
	 * @return  string  The punyencoded mail
	 *
	 * @since   3.5
	 */
	public function emailToPunycode($text)
	{
		$pattern = '/(("mailto:)+[\w\.\-\+]+\@[^"?]+\.+[^."?]+("|\?))/';

		if (preg_match_all($pattern, $text, $matches))
		{
			foreach ($matches[0] as $match)
			{
				$match  = (string) str_replace(array('?', '"'), '', $match);
				$text   = (string) str_replace($match, JStringPunycode::emailToPunycode($match), $text);
			}
		}

		return $text;
	}

	/**
	 * Function to determine if contents of an attribute are safe
	 *
	 * @param   array  $attrSubSet  A 2 element array for attribute's name, value
	 *
	 * @return  boolean  True if bad code is detected
	 *
	 * @since   11.1
	 */
	public static function checkAttribute($attrSubSet)
	{
		$attrSubSet[0] = strtolower($attrSubSet[0]);
		$attrSubSet[1] = strtolower($attrSubSet[1]);

		return (((strpos($attrSubSet[1], 'expression') !== false) && ($attrSubSet[0]) == 'style')
			|| (strpos($attrSubSet[1], 'javascript:') !== false)
			|| (strpos($attrSubSet[1], 'behaviour:') !== false)
			|| (strpos($attrSubSet[1], 'vbscript:') !== false)
			|| (strpos($attrSubSet[1], 'mocha:') !== false)
			|| (strpos($attrSubSet[1], 'livescript:') !== false));
	}

	/**
	 * Checks an uploaded for suspicious naming and potential PHP contents which could indicate a hacking attempt.
	 *
	 * The options you can define are:
	 * null_byte                   Prevent files with a null byte in their name (buffer overflow attack)
	 * forbidden_extensions        Do not allow these strings anywhere in the file's extension
	 * php_tag_in_content          Do not allow `<?php` tag in content
	 * shorttag_in_content         Do not allow short tag `<?` in content
	 * shorttag_extensions         Which file extensions to scan for short tags in content
	 * fobidden_ext_in_content     Do not allow forbidden_extensions anywhere in content
	 * php_ext_content_extensions  Which file extensions to scan for .php in content
	 *
	 * This code is an adaptation and improvement of Admin Tools' UploadShield feature,
	 * relicensed and contributed by its author.
	 *
	 * @param   array  $file     An uploaded file descriptor
	 * @param   array  $options  The scanner options (see the code for details)
	 *
	 * @return  boolean  True of the file is safe
	 *
	 * @since   3.4
	 */
	public static function isSafeFile($file, $options = array())
	{
		$defaultOptions = array(
			// Null byte in file name
			'null_byte'                  => true,
			// Forbidden string in extension (e.g. php matched .php, .xxx.php, .php.xxx and so on)
			'forbidden_extensions'       => array(
				'php', 'phps', 'php5', 'php3', 'php4', 'inc', 'pl', 'cgi', 'fcgi', 'java', 'jar', 'py'
			),
			// <?php tag in file contents
			'php_tag_in_content'         => true,
			// <? tag in file contents
			'shorttag_in_content'        => true,
			// Which file extensions to scan for short tags
			'shorttag_extensions'        => array(
				'inc', 'phps', 'class', 'php3', 'php4', 'php5', 'txt', 'dat', 'tpl', 'tmpl'
			),
			// Forbidden extensions anywhere in the content
			'fobidden_ext_in_content'    => true,
			// Which file extensions to scan for .php in the content
			'php_ext_content_extensions' => array('zip', 'rar', 'tar', 'gz', 'tgz', 'bz2', 'tbz', 'jpa'),
		);

		$options = array_merge($defaultOptions, $options);

		// Make sure we can scan nested file descriptors
		$descriptors = $file;

		if (isset($file['name']) && isset($file['tmp_name']))
		{
			$descriptors = self::decodeFileData(
				array(
					$file['name'],
					$file['type'],
					$file['tmp_name'],
					$file['error'],
					$file['size']
				)
			);
		}

		// Handle non-nested descriptors (single files)
		if (isset($descriptors['name']))
		{
			$descriptors = array($descriptors);
		}

		// Scan all descriptors detected
		foreach ($descriptors as $fileDescriptor)
		{
			if (!isset($fileDescriptor['name']))
			{
				// This is a nested descriptor. We have to recurse.
				if (!self::isSafeFile($fileDescriptor, $options))
				{
					return false;
				}

				continue;
			}

			$tempNames     = $fileDescriptor['tmp_name'];
			$intendedNames = $fileDescriptor['name'];

			if (!is_array($tempNames))
			{
				$tempNames = array($tempNames);
			}

			if (!is_array($intendedNames))
			{
				$intendedNames = array($intendedNames);
			}

			$len = count($tempNames);

			for ($i = 0; $i < $len; $i++)
			{
				$tempName     = array_shift($tempNames);
				$intendedName = array_shift($intendedNames);

				// 1. Null byte check
				if ($options['null_byte'])
				{
					if (strstr($intendedName, "\x00"))
					{
						return false;
					}
				}

				// 2. PHP-in-extension check (.php, .php.xxx[.yyy[.zzz[...]]], .xxx[.yyy[.zzz[...]]].php)
				if (!empty($options['forbidden_extensions']))
				{
					$explodedName = explode('.', $intendedName);
					$explodedName =	array_reverse($explodedName);
					array_pop($explodedName);
					$explodedName = array_map('strtolower', $explodedName);

					/*
					 * DO NOT USE array_intersect HERE! array_intersect expects the two arrays to
					 * be set, i.e. they should have unique values.
					 */
					foreach ($options['forbidden_extensions'] as $ext)
					{
						if (in_array($ext, $explodedName))
						{
							return false;
						}
					}
				}

				// 3. File contents scanner (PHP tag in file contents)
				if ($options['php_tag_in_content'] || $options['shorttag_in_content']
					|| ($options['fobidden_ext_in_content'] && !empty($options['forbidden_extensions'])))
				{
					$fp = @fopen($tempName, 'r');

					if ($fp !== false)
					{
						$data = '';

						while (!feof($fp))
						{
							$data .= @fread($fp, 131072);

							if ($options['php_tag_in_content'] && stristr($data, '<?php'))
							{
								return false;
							}

							if ($options['shorttag_in_content'])
							{
								$suspiciousExtensions = $options['shorttag_extensions'];

								if (empty($suspiciousExtensions))
								{
									$suspiciousExtensions = array(
										'inc', 'phps', 'class', 'php3', 'php4', 'txt', 'dat', 'tpl', 'tmpl'
									);
								}

								/*
								 * DO NOT USE array_intersect HERE! array_intersect expects the two arrays to
								 * be set, i.e. they should have unique values.
								 */
								$collide = false;

								foreach ($suspiciousExtensions as $ext)
								{
									if (in_array($ext, $explodedName))
									{
										$collide = true;

										break;
									}
								}

								if ($collide)
								{
									// These are suspicious text files which may have the short tag (<?) in them
									if (strstr($data, '<?'))
									{
										return false;
									}
								}
							}

							if ($options['fobidden_ext_in_content'] && !empty($options['forbidden_extensions']))
							{
								$suspiciousExtensions = $options['php_ext_content_extensions'];

								if (empty($suspiciousExtensions))
								{
									$suspiciousExtensions = array(
										'zip', 'rar', 'tar', 'gz', 'tgz', 'bz2', 'tbz', 'jpa'
									);
								}

								/*
								 * DO NOT USE array_intersect HERE! array_intersect expects the two arrays to
								 * be set, i.e. they should have unique values.
								 */
								$collide = false;

								foreach ($suspiciousExtensions as $ext)
								{
									if (in_array($ext, $explodedName))
									{
										$collide = true;

										break;
									}
								}

								if ($collide)
								{
									/*
									 * These are suspicious text files which may have an executable
									 * file extension in them
									 */
									foreach ($options['forbidden_extensions'] as $ext)
									{
										if (strstr($data, '.' . $ext))
										{
											return false;
										}
									}
								}
							}

							/*
							 * This makes sure that we don't accidentally skip a <?php tag if it's across
							 * a read boundary, even on multibyte strings
							 */
							$data = substr($data, -10);
						}

						fclose($fp);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Method to decode a file data array.
	 *
	 * @param   array  $data  The data array to decode.
	 *
	 * @return  array
	 *
	 * @since   3.4
	 */
	protected static function decodeFileData(array $data)
	{
		$result = array();

		if (is_array($data[0]))
		{
			foreach ($data[0] as $k => $v)
			{
				$result[$k] = self::decodeFileData(array($data[0][$k], $data[1][$k], $data[2][$k], $data[3][$k], $data[4][$k]));
			}

			return $result;
		}

		return array('name' => $data[0], 'type' => $data[1], 'tmp_name' => $data[2], 'error' => $data[3], 'size' => $data[4]);
	}

	/**
	 * Checks an uploaded for suspicious naming and potential PHP contents which could indicate a hacking attempt.
	 *
	 * The options you can define are:
	 * null_byte                   Prevent files with a null byte in their name (buffer overflow attack)
	 * forbidden_extensions        Do not allow these strings anywhere in the file's extension
	 * php_tag_in_content          Do not allow <?php tag in content
	 * shorttag_in_content         Do not allow short tag <? in content
	 * shorttag_extensions         Which file extensions to scan for short tags in content
	 * fobidden_ext_in_content     Do not allow forbidden_extensions anywhere in content
	 * php_ext_content_extensions  Which file extensions to scan for .php in content
	 *
	 * This code is an adaptation and improvement of Admin Tools' UploadShield feature,
	 * relicensed and contributed by its author.
	 *
	 * @param   array  $file     An uploaded file descriptor
	 * @param   array  $options  The scanner options (see the code for details)
	 *
	 * @return  boolean  True of the file is safe
	 *
	 * @since   3.4
	 */
	public static function isSafeFile($file, $options = array())
	{
		$defaultOptions = array(
			// Null byte in file name
			'null_byte'                  => true,
			// Forbidden string in extension (e.g. php matched .php, .xxx.php, .php.xxx and so on)
			'forbidden_extensions'       => array(
				'php', 'phps', 'php5', 'php3', 'php4', 'inc', 'pl', 'cgi', 'fcgi', 'java', 'jar', 'py'
			),
			// <?php tag in file contents
			'php_tag_in_content'         => true,
			// <? tag in file contents
			'shorttag_in_content'        => true,
			// Which file extensions to scan for short tags
			'shorttag_extensions'        => array(
				'inc', 'phps', 'class', 'php3', 'php4', 'php5', 'txt', 'dat', 'tpl', 'tmpl'
			),
			// Forbidden extensions anywhere in the content
			'fobidden_ext_in_content'    => true,
			// Which file extensions to scan for .php in the content
			'php_ext_content_extensions' => array('zip', 'rar', 'tar', 'gz', 'tgz', 'bz2', 'tbz', 'jpa'),
		);

		$options = array_merge($defaultOptions, $options);

		// Make sure we can scan nested file descriptors
		$descriptors = $file;

		if (isset($file['name']) && isset($file['tmp_name']))
		{
			$descriptors = self::decodeFileData(
				array(
					$file['name'],
					$file['type'],
					$file['tmp_name'],
					$file['error'],
					$file['size']
				)
			);
		}

		// Handle non-nested descriptors (single files)
		if (isset($descriptors['name']))
		{
			$descriptors = array($descriptors);
		}

		// Scan all descriptors detected
		foreach ($descriptors as $fileDescriptor)
		{
			if (!isset($fileDescriptor['name']))
			{
				// This is a nested descriptor. We have to recurse.
				if (!self::isSafeFile($fileDescriptor, $options))
				{
					return false;
				}

				continue;
			}

			$tempNames     = $fileDescriptor['tmp_name'];
			$intendedNames = $fileDescriptor['name'];

			if (!is_array($tempNames))
			{
				$tempNames = array($tempNames);
			}

			if (!is_array($intendedNames))
			{
				$intendedNames = array($intendedNames);
			}

			$len = count($tempNames);

			for ($i = 0; $i < $len; $i++)
			{
				$tempName     = array_shift($tempNames);
				$intendedName = array_shift($intendedNames);

				// 1. Null byte check
				if ($options['null_byte'])
				{
					if (strstr($intendedName, "\x00"))
					{
						return false;
					}
				}

				// 2. PHP-in-extension check (.php, .php.xxx[.yyy[.zzz[...]]], .xxx[.yyy[.zzz[...]]].php)
				if (!empty($options['forbidden_extensions']))
				{
					$explodedName = explode('.', $intendedName);
					$explodedName =	array_reverse($explodedName);
					array_pop($explodedName);
					$explodedName = array_map('strtolower', $explodedName);

					/*
					 * DO NOT USE array_intersect HERE! array_intersect expects the two arrays to
					 * be set, i.e. they should have unique values.
					 */
					foreach ($options['forbidden_extensions'] as $ext)
					{
						if (in_array($ext, $explodedName))
						{
							return false;
						}
					}
				}

				// 3. File contents scanner (PHP tag in file contents)
				if ($options['php_tag_in_content'] || $options['shorttag_in_content']
					|| ($options['fobidden_ext_in_content'] && !empty($options['forbidden_extensions'])))
				{
					$fp = @fopen($tempName, 'r');

					if ($fp !== false)
					{
						$data = '';

						while (!feof($fp))
						{
							$data .= @fread($fp, 131072);

							if ($options['php_tag_in_content'] && stristr($data, '<?php'))
							{
								return false;
							}

							if ($options['shorttag_in_content'])
							{
								$suspiciousExtensions = $options['shorttag_extensions'];

								if (empty($suspiciousExtensions))
								{
									$suspiciousExtensions = array(
										'inc', 'phps', 'class', 'php3', 'php4', 'txt', 'dat', 'tpl', 'tmpl'
									);
								}

								/*
								 * DO NOT USE array_intersect HERE! array_intersect expects the two arrays to
								 * be set, i.e. they should have unique values.
								 */
								$collide = false;

								foreach ($suspiciousExtensions as $ext)
								{
									if (in_array($ext, $explodedName))
									{
										$collide = true;

										break;
									}
								}

								if ($collide)
								{
									// These are suspicious text files which may have the short tag (<?) in them
									if (strstr($data, '<?'))
									{
										return false;
									}
								}
							}

							if ($options['fobidden_ext_in_content'] && !empty($options['forbidden_extensions']))
							{
								$suspiciousExtensions = $options['php_ext_content_extensions'];

								if (empty($suspiciousExtensions))
								{
									$suspiciousExtensions = array(
										'zip', 'rar', 'tar', 'gz', 'tgz', 'bz2', 'tbz', 'jpa'
									);
								}

								/*
								 * DO NOT USE array_intersect HERE! array_intersect expects the two arrays to
								 * be set, i.e. they should have unique values.
								 */
								$collide = false;

								foreach ($suspiciousExtensions as $ext)
								{
									if (in_array($ext, $explodedName))
									{
										$collide = true;

										break;
									}
								}

								if ($collide)
								{
									/*
									 * These are suspicious text files which may have an executable
									 * file extension in them
									 */
									foreach ($options['forbidden_extensions'] as $ext)
									{
										if (strstr($data, '.' . $ext))
										{
											return false;
										}
									}
								}
							}

							/*
							 * This makes sure that we don't accidentally skip a <?php tag if it's across
							 * a read boundary, even on multibyte strings
							 */
							$data = substr($data, -10);
						}

						fclose($fp);
					}
				}
			}
		}

		return true;
	}

	/**
	 * Method to decode a file data array.
	 *
	 * @param   array  $data  The data array to decode.
	 *
	 * @return  array
	 *
	 * @since   3.4
	 */
	protected static function decodeFileData(array $data)
	{
		$result = array();

		if (is_array($data[0]))
		{
			foreach ($data[0] as $k => $v)
			{
				$result[$k] = self::decodeFileData(array($data[0][$k], $data[1][$k], $data[2][$k], $data[3][$k], $data[4][$k]));
			}

			return $result;
		}

		return array('name' => $data[0], 'type' => $data[1], 'tmp_name' => $data[2], 'error' => $data[3], 'size' => $data[4]);
	}

	/**
	 * Internal method to iteratively remove all unwanted tags and attributes
	 *
	 * @param   string  $source  Input string to be 'cleaned'
	 *
	 * @return  string  'Cleaned' version of input parameter
	 *
	 * @since      11.1
	 * @deprecated 4.0 Use JFilterInput::remove() instead
	 */
	protected function _remove($source)
	{
		return $this->remove($source);
	}
<<<<<<< HEAD

	/**
	 * Internal method to iteratively remove all unwanted tags and attributes
	 *
	 * @param   string  $source  Input string to be 'cleaned'
	 *
	 * @return  string  'Cleaned' version of input parameter
	 *
	 * @since   3.5
	 */
	protected function remove($source)
	{
		$loopCounter = 0;
=======
>>>>>>> joomla/staging

	/**
	 * Internal method to iteratively remove all unwanted tags and attributes
	 *
	 * @param   string  $source  Input string to be 'cleaned'
	 *
	 * @return  string  'Cleaned' version of input parameter
	 *
	 * @since   3.5
	 */
	protected function remove($source)
	{
		// Iteration provides nested tag protection
		do
		{
			$temp = $source;
			$source = $this->_cleanTags($source);
		}
		while ($temp != $source);

		return $source;
	}

	/**
	 * Internal method to strip a string of certain tags
	 *
	 * @param   string  $source  Input string to be 'cleaned'
	 *
	 * @return  string  'Cleaned' version of input parameter
	 *
	 * @since      11.1
	 * @deprecated 4.0 Use JFilterInput::cleanTags() instead
	 */
	protected function _cleanTags($source)
	{
		return $this->cleanTags($source);
	}

	/**
	 * Internal method to strip a string of certain tags
	 *
	 * @param   string  $source  Input string to be 'cleaned'
	 *
	 * @return  string  'Cleaned' version of input parameter
	 *
	 * @since   3.5
	 */
	protected function cleanTags($source)
	{
		// First, pre-process this for illegal characters inside attribute values
		$source = $this->_escapeAttributeValues($source);

		// In the beginning we don't really have a tag, so everything is postTag
		$preTag = null;
		$postTag = $source;
		$currentSpace = false;

		// Setting to null to deal with undefined variables
		$attr = '';

		// Is there a tag? If so it will certainly start with a '<'.
		$tagOpen_start = strpos($source, '<');

		while ($tagOpen_start !== false)
		{
			// Get some information about the tag we are processing
			$preTag .= substr($postTag, 0, $tagOpen_start);
			$postTag = substr($postTag, $tagOpen_start);
			$fromTagOpen = substr($postTag, 1);
			$tagOpen_end = strpos($fromTagOpen, '>');

			// Check for mal-formed tag where we have a second '<' before the first '>'
			$nextOpenTag = (strlen($postTag) > $tagOpen_start) ? strpos($postTag, '<', $tagOpen_start + 1) : false;

			if (($nextOpenTag !== false) && ($nextOpenTag < $tagOpen_end))
			{
				// At this point we have a mal-formed tag -- remove the offending open
				$postTag = substr($postTag, 0, $tagOpen_start) . substr($postTag, $tagOpen_start + 1);
				$tagOpen_start = strpos($postTag, '<');
				continue;
			}

			// Let's catch any non-terminated tags and skip over them
			if ($tagOpen_end === false)
			{
				$postTag = substr($postTag, $tagOpen_start + 1);
				$tagOpen_start = strpos($postTag, '<');
				continue;
			}

			// Do we have a nested tag?
			$tagOpen_nested = strpos($fromTagOpen, '<');

			if (($tagOpen_nested !== false) && ($tagOpen_nested < $tagOpen_end))
			{
				$preTag .= substr($postTag, 0, ($tagOpen_nested + 1));
				$postTag = substr($postTag, ($tagOpen_nested + 1));
				$tagOpen_start = strpos($postTag, '<');
				continue;
			}

			// Let's get some information about our tag and setup attribute pairs
			$tagOpen_nested = (strpos($fromTagOpen, '<') + $tagOpen_start + 1);
			$currentTag = substr($fromTagOpen, 0, $tagOpen_end);
			$tagLength = strlen($currentTag);
			$tagLeft = $currentTag;
			$attrSet = array();
			$currentSpace = strpos($tagLeft, ' ');

			// Are we an open tag or a close tag?
			if (substr($currentTag, 0, 1) == '/')
			{
				// Close Tag
				$isCloseTag = true;
				list ($tagName) = explode(' ', $currentTag);
				$tagName = substr($tagName, 1);
			}
			else
			{
				// Open Tag
				$isCloseTag = false;
				list ($tagName) = explode(' ', $currentTag);
			}

			/*
			 * Exclude all "non-regular" tagnames
			 * OR no tagname
			 * OR remove if xssauto is on and tag is blacklisted
			 */
			if ((!preg_match("/^[a-z][a-z0-9]*$/i", $tagName)) || (!$tagName) || ((in_array(strtolower($tagName), $this->tagBlacklist)) && ($this->xssAuto)))
			{
				$postTag = substr($postTag, ($tagLength + 2));
				$tagOpen_start = strpos($postTag, '<');

				// Strip tag
				continue;
			}

			/*
			 * Time to grab any attributes from the tag... need this section in
			 * case attributes have spaces in the values.
			 */
			while ($currentSpace !== false)
			{
				$attr = '';
				$fromSpace = substr($tagLeft, ($currentSpace + 1));
				$nextEqual = strpos($fromSpace, '=');
				$nextSpace = strpos($fromSpace, ' ');
				$openQuotes = strpos($fromSpace, '"');
				$closeQuotes = strpos(substr($fromSpace, ($openQuotes + 1)), '"') + $openQuotes + 1;

				$startAtt = '';
				$startAttPosition = 0;

				// Find position of equal and open quotes ignoring
				if (preg_match('#\s*=\s*\"#', $fromSpace, $matches, PREG_OFFSET_CAPTURE))
				{
					$startAtt = $matches[0][0];
					$startAttPosition = $matches[0][1];
					$closeQuotes = strpos(substr($fromSpace, ($startAttPosition + strlen($startAtt))), '"') + $startAttPosition + strlen($startAtt);
					$nextEqual = $startAttPosition + strpos($startAtt, '=');
					$openQuotes = $startAttPosition + strpos($startAtt, '"');
					$nextSpace = strpos(substr($fromSpace, $closeQuotes), ' ') + $closeQuotes;
				}

				// Do we have an attribute to process? [check for equal sign]
				if ($fromSpace != '/' && (($nextEqual && $nextSpace && $nextSpace < $nextEqual) || !$nextEqual))
				{
					if (!$nextEqual)
					{
						$attribEnd = strpos($fromSpace, '/') - 1;
					}
					else
					{
						$attribEnd = $nextSpace - 1;
					}
					// If there is an ending, use this, if not, do not worry.
					if ($attribEnd > 0)
					{
						$fromSpace = substr($fromSpace, $attribEnd + 1);
					}
				}

				if (strpos($fromSpace, '=') !== false)
				{
					// If the attribute value is wrapped in quotes we need to grab the substring from
					// the closing quote, otherwise grab until the next space.
					if (($openQuotes !== false) && (strpos(substr($fromSpace, ($openQuotes + 1)), '"') !== false))
					{
						$attr = substr($fromSpace, 0, ($closeQuotes + 1));
					}
					else
					{
						$attr = substr($fromSpace, 0, $nextSpace);
					}
				}
				// No more equal signs so add any extra text in the tag into the attribute array [eg. checked]
				else
				{
					if ($fromSpace != '/')
					{
						$attr = substr($fromSpace, 0, $nextSpace);
					}
				}

				// Last Attribute Pair
				if (!$attr && $fromSpace != '/')
				{
					$attr = $fromSpace;
				}

				// Add attribute pair to the attribute array
				$attrSet[] = $attr;

				// Move search point and continue iteration
				$tagLeft = substr($fromSpace, strlen($attr));
				$currentSpace = strpos($tagLeft, ' ');
			}

			// Is our tag in the user input array?
			$tagFound = in_array(strtolower($tagName), $this->tagsArray);

			// If the tag is allowed let's append it to the output string.
			if ((!$tagFound && $this->tagsMethod) || ($tagFound && !$this->tagsMethod))
			{
				// Reconstruct tag with allowed attributes
				if (!$isCloseTag)
				{
					// Open or single tag
					$attrSet = $this->_cleanAttributes($attrSet);
					$preTag .= '<' . $tagName;

					for ($i = 0, $count = count($attrSet); $i < $count; $i++)
					{
						$preTag .= ' ' . $attrSet[$i];
					}

					// Reformat single tags to XHTML
					if (strpos($fromTagOpen, '</' . $tagName))
					{
						$preTag .= '>';
					}
					else
					{
						$preTag .= ' />';
					}
				}
				// Closing tag
				else
				{
					$preTag .= '</' . $tagName . '>';
				}
			}

			// Find next tag's start and continue iteration
			$postTag = substr($postTag, ($tagLength + 2));
			$tagOpen_start = strpos($postTag, '<');
		}

		// Append any code after the end of tags and return
		if ($postTag != '<')
		{
			$preTag .= $postTag;
		}

		return $preTag;
	}

	/**
	 * Internal method to strip a tag of certain attributes
	 *
	 * @param   array  $attrSet  Array of attribute pairs to filter
	 *
	 * @return  array  Filtered array of attribute pairs
	 *
	 * @since      11.1
	 * @deprecated 4.0 Use JFilterInput::cleanAttributes() instead
	 */
	protected function _cleanAttributes($attrSet)
	{
		return $this->cleanAttributes($attrSet);
	}

	/**
	 * Internal method to strip a tag of certain attributes
	 *
	 * @param   array  $attrSet  Array of attribute pairs to filter
	 *
	 * @return  array  Filtered array of attribute pairs
	 *
	 * @since   3.5
	 */
	protected function cleanAttributes($attrSet)
	{
		$newSet = array();

		$count = count($attrSet);

		// Iterate through attribute pairs
		for ($i = 0; $i < $count; $i++)
		{
			// Skip blank spaces
			if (!$attrSet[$i])
			{
				continue;
			}

			// Split into name/value pairs
			$attrSubSet = explode('=', trim($attrSet[$i]), 2);

			// Take the last attribute in case there is an attribute with no value
			$attrSubSet_0 = explode(' ', trim($attrSubSet[0]));
			$attrSubSet[0] = array_pop($attrSubSet_0);

			// Remove all "non-regular" attribute names
			// AND blacklisted attributes

			if ((!preg_match('/[a-z]*$/i', $attrSubSet[0]))
				|| (($this->xssAuto) && ((in_array(strtolower($attrSubSet[0]), $this->attrBlacklist))
				|| (substr($attrSubSet[0], 0, 2) == 'on'))))
			{
				continue;
			}

			// XSS attribute value filtering
			if (isset($attrSubSet[1]))
			{
				// Trim leading and trailing spaces
				$attrSubSet[1] = trim($attrSubSet[1]);

				// Strips unicode, hex, etc
				$attrSubSet[1] = str_replace('&#', '', $attrSubSet[1]);

				// Strip normal newline within attr value
				$attrSubSet[1] = preg_replace('/[\n\r]/', '', $attrSubSet[1]);

				// Strip double quotes
				$attrSubSet[1] = str_replace('"', '', $attrSubSet[1]);

				// Convert single quotes from either side to doubles (Single quotes shouldn't be used to pad attr values)
				if ((substr($attrSubSet[1], 0, 1) == "'") && (substr($attrSubSet[1], (strlen($attrSubSet[1]) - 1), 1) == "'"))
				{
					$attrSubSet[1] = substr($attrSubSet[1], 1, (strlen($attrSubSet[1]) - 2));
				}
				// Strip slashes
				$attrSubSet[1] = stripslashes($attrSubSet[1]);
			}
			else
			{
				continue;
			}

			// Autostrip script tags
			if (self::checkAttribute($attrSubSet))
			{
				continue;
			}

			// Is our attribute in the user input array?
			$attrFound = in_array(strtolower($attrSubSet[0]), $this->attrArray);

			// If the tag is allowed lets keep it
			if ((!$attrFound && $this->attrMethod) || ($attrFound && !$this->attrMethod))
			{
				// Does the attribute have a value?
				if (empty($attrSubSet[1]) === false)
				{
					$newSet[] = $attrSubSet[0] . '="' . $attrSubSet[1] . '"';
				}
				elseif ($attrSubSet[1] === "0")
				{
					// Special Case
					// Is the value 0?
					$newSet[] = $attrSubSet[0] . '="0"';
				}
				else
				{
					// Leave empty attributes alone
					$newSet[] = $attrSubSet[0] . '=""';
				}
			}
		}

		return $newSet;
	}

	/**
	 * Try to convert to plaintext
	 *
	 * @param   string  $source  The source string.
	 *
	 * @return  string  Plaintext string
	 *
	 * @since      11.1
	 * @deprecated 4.0 Use JFilterInput::decode() instead
	 */
	protected function _decode($source)
	{
		return $this->decode($source);
	}

	/**
	 * Try to convert to plaintext
	 *
	 * @param   string  $source  The source string.
	 *
	 * @return  string  Plaintext string
	 *
	 * @since   3.5
	 */
	protected function decode($source)
	{
		static $ttr;

		if (!is_array($ttr))
		{
			// Entity decode
			$trans_tbl = get_html_translation_table(HTML_ENTITIES, ENT_COMPAT, 'ISO-8859-1');

			foreach ($trans_tbl as $k => $v)
			{
				$ttr[$v] = utf8_encode($k);
			}
		}

		$source = strtr($source, $ttr);

		// Convert decimal
		$source = preg_replace_callback('/&#(\d+);/m', function($m)
		{
			return utf8_encode(chr($m[1]));
		}, $source
		);

		// Convert hex
		$source = preg_replace_callback('/&#x([a-f0-9]+);/mi', function($m)
		{
			return utf8_encode(chr('0x' . $m[1]));
		}, $source
		);

		return $source;
	}

	/**
	 * Escape < > and " inside attribute values
	 *
	 * @param   string  $source  The source string.
	 *
	 * @return  string  Filtered string
	 *
	 * @since      11.1
	 * @deprecated 4.0 Use JFilterInput::escapeAttributeValues() instead
	 */
	protected function _escapeAttributeValues($source)
	{
		return $this->escapeAttributeValues($source);
	}

	/**
	 * Escape < > and " inside attribute values
	 *
	 * @param   string  $source  The source string.
	 *
	 * @return  string  Filtered string
	 *
	 * @since    3.5
	 */
	protected function escapeAttributeValues($source)
	{
		$alreadyFiltered = '';
		$remainder = $source;
		$badChars = array('<', '"', '>');
		$escapedChars = array('&lt;', '&quot;', '&gt;');

		// Process each portion based on presence of =" and "<space>, "/>, or ">
		// See if there are any more attributes to process
		while (preg_match('#<[^>]*?=\s*?(\"|\')#s', $remainder, $matches, PREG_OFFSET_CAPTURE))
		{
			// Get the portion before the attribute value
			$quotePosition = $matches[0][1];
			$nextBefore = $quotePosition + strlen($matches[0][0]);

			// Figure out if we have a single or double quote and look for the matching closing quote
			// Closing quote should be "/>, ">, "<space>, or " at the end of the string
			$quote = substr($matches[0][0], -1);
			$pregMatch = ($quote == '"') ? '#(\"\s*/\s*>|\"\s*>|\"\s+|\"$)#' : "#(\'\s*/\s*>|\'\s*>|\'\s+|\'$)#";

			// Get the portion after attribute value
			if (preg_match($pregMatch, substr($remainder, $nextBefore), $matches, PREG_OFFSET_CAPTURE))
			{
				// We have a closing quote
				$nextAfter = $nextBefore + $matches[0][1];
			}
			else
			{
				// No closing quote
				$nextAfter = strlen($remainder);
			}

			// Get the actual attribute value
			$attributeValue = substr($remainder, $nextBefore, $nextAfter - $nextBefore);

			// Escape bad chars
			$attributeValue = str_replace($badChars, $escapedChars, $attributeValue);
			$attributeValue = $this->_stripCSSExpressions($attributeValue);
			$alreadyFiltered .= substr($remainder, 0, $nextBefore) . $attributeValue . $quote;
			$remainder = substr($remainder, $nextAfter + 1);
		}

		// At this point, we just have to return the $alreadyFiltered and the $remainder
		return $alreadyFiltered . $remainder;
	}

	/**
	 * Remove CSS Expressions in the form of `<property>:expression(...)`
	 *
	 * @param   string  $source  The source string.
	 *
	 * @return  string  Filtered string
	 *
	 * @since       11.1
	 * @deprecated  4.0 Use JFilterInput::stripCSSExpressions() instead
	 */
	protected function _stripCSSExpressions($source)
	{
		return $this->stripCSSExpressions($source);
	}

	/**
	 * Remove CSS Expressions in the form of <property>:expression(...)
	 *
	 * @param   string  $source  The source string.
	 *
	 * @return  string  Filtered string
	 *
	 * @since   3.5
	 */
	protected function stripCSSExpressions($source)
	{
		// Strip any comments out (in the form of /*...*/)
		$test = preg_replace('#\/\*.*\*\/#U', '', $source);

		// Test for :expression
		if (!stripos($test, ':expression'))
		{
			// Not found, so we are done
			$return = $source;
		}
		else
		{
			// At this point, we have stripped out the comments and have found :expression
			// Test stripped string for :expression followed by a '('
			if (preg_match_all('#:expression\s*\(#', $test, $matches))
			{
				// If found, remove :expression
				$test = str_ireplace(':expression', '', $test);
				$return = $test;
			}
		}

		return $return;
	}

	/**
	 * Recursively strip Unicode Supplementary Characters from the source. Not: objects cannot be filtered.
	 *
	 * @param   mixed  $source  The data to filter
	 *
	 * @return  mixed  The filtered result
	 *
	 * @since   3.5
	 */
	protected function stripUSC($source)
	{
		if (is_object($source))
		{
			return $source;
		}

		if (is_array($source))
		{
			$filteredArray = array();

			foreach ($source as $k => $v)
			{
				$filteredArray[$k] = $this->stripUSC($v);
			}

			return $filteredArray;
		}

		return preg_replace('/[\xF0-\xF7].../s', "\xE2\xAF\x91", $source);
	}
}
