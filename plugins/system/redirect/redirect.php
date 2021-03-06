<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.redirect
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

use Joomla\Registry\Registry;

/**
 * Plugin class for redirect handling.
 *
 * @since  1.6
 */
class PlgSystemRedirect extends JPlugin
{
	/**
	 * Affects constructor behavior. If true, language files will be loaded automatically.
	 *
	 * @var    boolean
	 * @since  3.4
	 */
	protected $autoloadLanguage = false;

	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe
	 * @param   array   $config    An optional associative array of configuration settings.
	 *
	 * @since   1.6
	 */
	public function __construct(&$subject, $config)
	{
		parent::__construct($subject, $config);

		// Set the error handler for E_ERROR to be the class handleError method.
		JError::setErrorHandling(E_ERROR, 'callback', array('PlgSystemRedirect', 'handleError'));
		set_exception_handler(array('PlgSystemRedirect', 'handleException'));
	}

	/**
	 * Method to handle an error condition from JError.
	 *
	 * @param   JException  &$error  The JException object to be handled.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function handleError(JException &$error)
	{
		self::doErrorHandling($error);
	}

	/**
<<<<<<< HEAD
	 * Method to handle an error condition.
	 *
	 * @param   Exception  &$error  The Exception object to be handled.
	 *
	 * @return  void
	 *
	 * @since   1.6
	 */
	public static function handleError(&$error)
=======
	 * Method to handle an uncaught exception.
	 *
	 * @param   Exception|Throwable  $exception  The Exception or Throwable object to be handled.
	 *
	 * @return  void
	 *
	 * @since   3.5
	 * @throws  InvalidArgumentException
	 */
	public static function handleException($exception)
	{
		// If this isn't a Throwable then bail out
		if (!($exception instanceof Throwable) && !($exception instanceof Exception))
		{
			throw new InvalidArgumentException(
				sprintf('The error handler requires a Exception or Throwable object, a "%s" object was given instead.', get_class($exception))
			);
		}

		self::doErrorHandling($exception);
	}

	/**
	 * Internal processor for all error handlers
	 *
	 * @param   Exception|Throwable  $error  The Exception or Throwable object to be handled.
	 *
	 * @return  void
	 *
	 * @since   3.5
	 */
	private static function doErrorHandling($error)
>>>>>>> joomla/staging
	{
		// Get the application object.
		$app = JFactory::getApplication();

		// Make sure the error is a 404 and we are not in the administrator.
		if ($app->isAdmin() || $error->getCode() != 404)
<<<<<<< HEAD
		{
			// Render the error page.
			JError::customErrorPage($error);
		}

		// Get the full current URI.
		$uri     = JUri::getInstance();
		$current = rawurldecode($uri->toString(array('scheme', 'host', 'port', 'path', 'query', 'fragment')));

		// Attempt to ignore idiots.
		if ((strpos($current, 'mosConfig_') !== false) || (strpos($current, '=http://') !== false))
		{
			// Render the error page.
			JError::customErrorPage($error);
		}

		// See if the current url exists in the database as a redirect.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('new_url', 'header')))
			->select($db->quoteName('published'))
			->from($db->quoteName('#__redirect_links'))
			->where($db->quoteName('old_url') . ' = ' . $db->quote($current));
		$db->setQuery($query, 0, 1);
		$link = $db->loadObject();

		// If no published redirect was found try with the server-relative URL
		if (!$link or ($link->published != 1))
=======
		{
			// Render the error page.
			JErrorPage::render($error);
		}

		// Get the full current URI.
		$uri     = JUri::getInstance();
		$current = rawurldecode($uri->toString(array('scheme', 'host', 'port', 'path', 'query', 'fragment')));

		// Attempt to ignore idiots.
		if ((strpos($current, 'mosConfig_') !== false) || (strpos($current, '=http://') !== false))
		{
			// Render the error page.
			JErrorPage::render($error);
		}

		// See if the current url exists in the database as a redirect.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true)
			->select($db->quoteName(array('new_url', 'header')))
			->select($db->quoteName('published'))
			->from($db->quoteName('#__redirect_links'))
			->where($db->quoteName('old_url') . ' = ' . $db->quote($current));
		$db->setQuery($query, 0, 1);
		$link = $db->loadObject();

		// If no published redirect was found try with the server-relative URL
		if (!$link || ($link->published != 1))
>>>>>>> joomla/staging
		{
			$currRel = rawurldecode($uri->toString(array('path', 'query', 'fragment')));
			$query = $db->getQuery(true)
				->select($db->quoteName('new_url'))
				->select($db->quoteName('published'))
				->from($db->quoteName('#__redirect_links'))
				->where($db->quoteName('old_url') . ' = ' . $db->quote($currRel));
			$db->setQuery($query, 0, 1);
			$link = $db->loadObject();
		}

		// If a redirect exists and is published, permanently redirect.
<<<<<<< HEAD
		if ($link and ($link->published == 1))
=======
		if ($link && ($link->published == 1))
>>>>>>> joomla/staging
		{
			// If no header is set use a 301 permanent redirect
			if (!$link->header || JComponentHelper::getParams('com_redirect')->get('mode', 0) == false)
			{
				$link->header = 301;
<<<<<<< HEAD
			}

			// If we have a redirect in the 300 range use JApplicationWeb::redirect().
			if ($link->header < 400 && $link->header >= 300)
			{
				$new_link = JUri::isInternal($link->new_url) ? JRoute::_($link->new_url) : $link->new_url;

				$app->redirect($new_link, intval($link->header));
=======
>>>>>>> joomla/staging
			}

			// If we have a redirect in the 300 range use JApplicationWeb::redirect().
			if ($link->header < 400 && $link->header >= 300)
			{
<<<<<<< HEAD
				// Else rethrow the exeception with the new header and return
				try
				{
					throw new RuntimeException($error->getMessage(), $link->header, $error);
				}
				catch (Exception $e)
				{
					$newError = $e;
				}

				JError::customErrorPage($newError);
			}
		}
		else
		{
			try
			{
				$referer = empty($_SERVER['HTTP_REFERER']) ? '' : $_SERVER['HTTP_REFERER'];
				$query   = $db->getQuery(true)
					->select($db->quoteName('id'))
					->from($db->quoteName('#__redirect_links'))
					->where($db->quoteName('old_url') . ' = ' . $db->quote($current));
				$db->setQuery($query);
				$res = $db->loadResult();

				if (!$res)
				{
					// If not, add the new url to the database but only if option is enabled
					$params       = new Registry(JPluginHelper::getPlugin('system', 'redirect')->params);
					$collect_urls = $params->get('collect_urls', 1);

					if ($collect_urls == true)
					{
						$columns = array(
							$db->quoteName('old_url'),
							$db->quoteName('new_url'),
							$db->quoteName('referer'),
							$db->quoteName('comment'),
							$db->quoteName('hits'),
							$db->quoteName('published'),
							$db->quoteName('created_date')
						);
						$query->clear()
							->insert($db->quoteName('#__redirect_links'), false)
							->columns($columns)
							->values(
								$db->quote($current) . ', ' . $db->quote('') .
								' ,' . $db->quote($referer) . ', ' . $db->quote('') . ',1,0, ' .
								$db->quote(JFactory::getDate()->toSql())
							);
=======
				$new_link = JUri::isInternal($link->new_url) ? JRoute::_($link->new_url) : $link->new_url;

				$app->redirect($new_link, intval($link->header));
			}

			// Else rethrow the exeception with the new header and return
			JErrorPage::render(new RuntimeException($error->getMessage(), $link->header, $error));
		}

		try
		{
			$referer = $app->input->server->getString('HTTP_REFERER', '');
			$query   = $db->getQuery(true)
				->select($db->quoteName('id'))
				->from($db->quoteName('#__redirect_links'))
				->where($db->quoteName('old_url') . ' = ' . $db->quote($current));
			$db->setQuery($query);
			$res = $db->loadResult();

			if (!$res)
			{
				// If not, add the new url to the database but only if option is enabled
				$params       = new Registry(JPluginHelper::getPlugin('system', 'redirect')->params);
				$collect_urls = $params->get('collect_urls', 1);

				if ($collect_urls == true)
				{
					$columns = array(
						$db->quoteName('old_url'),
						$db->quoteName('new_url'),
						$db->quoteName('referer'),
						$db->quoteName('comment'),
						$db->quoteName('hits'),
						$db->quoteName('published'),
						$db->quoteName('created_date')
					);

					$values = array(
						$db->quote($current),
						$db->quote(''),
						$db->quote($referer),
						$db->quote(''),
						1,
						0,
						$db->quote(JFactory::getDate()->toSql())
					);

					$query->clear()
						->insert($db->quoteName('#__redirect_links'), false)
						->columns($columns)
						->values(implode(', ', $values));
>>>>>>> joomla/staging

						$db->setQuery($query);
						$db->execute();
					}
				}
<<<<<<< HEAD
				else
				{
					// Existing error url, increase hit counter.
					$query->clear()
						->update($db->quoteName('#__redirect_links'))
						->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
						->where('id = ' . (int) $res);
					$db->setQuery($query);
					$db->execute();
				}
			}
			catch (RuntimeException $exception)
			{
				JError::customErrorPage(new Exception(JText::_('PLG_SYSTEM_REDIRECT_ERROR_UPDATING_DATABASE'), 404));
			}

			// Render the error page.
			JError::customErrorPage($error);
=======
			}
			else
			{
				// Existing error url, increase hit counter.
				$query->clear()
					->update($db->quoteName('#__redirect_links'))
					->set($db->quoteName('hits') . ' = ' . $db->quoteName('hits') . ' + 1')
					->where('id = ' . (int) $res);
				$db->setQuery($query);
				$db->execute();
			}
		}
		catch (RuntimeException $exception)
		{
			JErrorPage::render(new Exception(JText::_('PLG_SYSTEM_REDIRECT_ERROR_UPDATING_DATABASE'), 404, $exception));
>>>>>>> joomla/staging
		}

		// Render the error page.
		JErrorPage::render($error);
	}
}
