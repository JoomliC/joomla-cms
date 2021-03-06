<?php
/**
 * @package     Joomla.Administrator
 * @subpackage  com_languages
 *
 * @copyright   Copyright (C) 2005 - 2016 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('_JEXEC') or die;

// Add specific helper files for html generation
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

JHtml::_('formbehavior.chosen', 'select');
<<<<<<< HEAD

$user     = JFactory::getUser();
$userId   = $user->get('id');
$client   = $this->state->get('filter.client_id', 0) ? JText::_('JADMINISTRATOR') : JText::_('JSITE');
$clientId = $this->state->get('filter.client_id', 0);
?>

<form action="<?php echo JRoute::_('index.php?option=com_languages&view=installed&client=' . $clientId); ?>" method="post" id="adminForm" name="adminForm">
=======
JHtml::_('bootstrap.tooltip');

$user      = JFactory::getUser();
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn  = $this->escape($this->state->get('list.direction'));
?>
<form action="<?php echo JRoute::_('index.php?option=com_languages&view=installed'); ?>" method="post" id="adminForm" name="adminForm">
>>>>>>> joomla/staging
<?php if (!empty($this->sidebar)) : ?>
	<div id="j-sidebar-container" class="span2">
		<?php echo $this->sidebar; ?>
	</div>
	<div id="j-main-container" class="span10">
<?php else : ?>
	<div id="j-main-container">
<?php endif; ?>
<<<<<<< HEAD
		<div id="filter-bar" class="btn-toolbar">
			<div class="btn-group pull-right hidden-phone">
				<label for="limit" class="element-invisible"><?php echo JText::_('JFIELD_PLG_SEARCH_SEARCHLIMIT_DESC'); ?></label>
				<?php echo $this->pagination->getLimitBox(); ?>
			</div>
		</div>
		<div class="clearfix"> </div>
=======
		<?php echo JLayoutHelper::render('joomla.searchtools.default', array('view' => $this, 'options' => array('filterButton' => false))); ?>
		<div class="clearfix"></div>
		<?php if (empty($this->rows)) : ?>
		<div class="alert alert-no-items">
			<?php echo JText::_('JGLOBAL_NO_MATCHING_RESULTS'); ?>
		</div>
		<?php else : ?>
>>>>>>> joomla/staging
		<table class="table table-striped">
			<thead>
				<tr>
					<th width="1%">
						&#160;
					</th>
					<th width="25%" class="nowrap">
						<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_LANGUAGE', 'name', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap">
						<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_LANG_TAG', 'language', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap center">
						<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_DEFAULT', 'published', $listDirn, $listOrder); ?>
					</th>
<<<<<<< HEAD
					<th class="center">
						<?php echo JText::_('COM_LANGUAGES_HEADING_DEFAULT'); ?>
					</th>
					<th class="hidden-phone">
						<?php echo JText::_('JVERSION'); ?>
					</th>
					<th class="hidden-phone">
						<?php echo JText::_('JDATE'); ?>
					</th>
					<th class="hidden-phone">
						<?php echo JText::_('JAUTHOR'); ?>
					</th>
					<th class="hidden-phone">
						<?php echo JText::_('COM_LANGUAGES_HEADING_AUTHOR_EMAIL'); ?>
=======
					<th class="nowrap center">
						<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_VERSION', 'version', $listDirn, $listOrder); ?>
					</th>
					<th class="hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_DATE', 'creationDate', $listDirn, $listOrder); ?>
					</th>
					<th class="hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_AUTHOR', 'author', $listDirn, $listOrder); ?>
					</th>
					<th class="hidden-phone hidden-tablet">
						<?php echo JHtml::_('searchtools.sort', 'COM_LANGUAGES_HEADING_AUTHOR_EMAIL', 'authorEmail', $listDirn, $listOrder); ?>
					</th>
					<th class="nowrap hidden-phone">
						<?php echo JHtml::_('searchtools.sort', 'JGRID_HEADING_ID', 'extension_id', $listDirn, $listOrder); ?>
>>>>>>> joomla/staging
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="9">
						<?php echo $this->pagination->getListFooter(); ?>
					</td>
				</tr>
			</tfoot>
			<tbody>
			<?php
			$version = new JVersion;
			$currentShortVersion = preg_replace('#^([0-9\.]+)(|.*)$#', '$1', $version->getShortVersion());
			foreach ($this->rows as $i => $row) :
				$canCreate = $user->authorise('core.create',     'com_languages');
				$canEdit   = $user->authorise('core.edit',       'com_languages');
				$canChange = $user->authorise('core.edit.state', 'com_languages');
			?>
				<tr class="row<?php echo $i % 2; ?>">
					<td>
						<?php echo JHtml::_('languages.id', $i, $row->language); ?>
					</td>
<<<<<<< HEAD
					<td width="25%">
=======
					<td>
>>>>>>> joomla/staging
						<label for="cb<?php echo $i; ?>">
							<?php echo $this->escape($row->name); ?>
						</label>
					</td>
					<td>
						<?php echo $this->escape($row->language); ?>
					</td>
<<<<<<< HEAD
					<td>
						<?php echo $client;?>
					</td>
					<td class="center">
						<?php echo JHtml::_('jgrid.isdefault', $row->published, $i, 'installed.', !$row->published && $canChange);?>
					</td>
					<td class="hidden-phone">
						<?php echo $this->escape($row->version); ?>
					</td>
					<td class="hidden-phone">
=======
					<td class="center">
						<?php echo JHtml::_('jgrid.isdefault', $row->published, $i, 'installed.', !$row->published && $canChange); ?>
					</td>
					<td class="center small">
					<?php // Display a Note if language pack version is not equal to Joomla version ?>
					<?php if (substr($row->version, 0, 3) != $version::RELEASE || substr($row->version, 0, 5) != $currentShortVersion) : ?>
						<span class="label label-warning hasTooltip" title="<?php echo JText::_('JGLOBAL_LANGUAGE_VERSION_NOT_PLATFORM'); ?>"><?php echo $row->version; ?></span>
					<?php else : ?>
						<span class="label label-success"><?php echo $row->version; ?></span>
					<?php endif; ?>
					</td>
					<td class="hidden-phone">
>>>>>>> joomla/staging
						<?php echo $this->escape($row->creationDate); ?>
					</td>
					<td class="hidden-phone">
						<?php echo $this->escape($row->author); ?>
					</td>
<<<<<<< HEAD
					<td class="hidden-phone">
=======
					<td class="hidden-phone hidden-tablet">
>>>>>>> joomla/staging
						<?php echo JStringPunycode::emailToUTF8($this->escape($row->authorEmail)); ?>
					</td>
					<td class="hidden-phone">
						<?php echo $this->escape($row->extension_id); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="boxchecked" value="0" />
		<?php echo JHtml::_('form.token'); ?>
	</div>
</form>
