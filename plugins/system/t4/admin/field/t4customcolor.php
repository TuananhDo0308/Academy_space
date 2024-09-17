<?php
/**
 * @package     Joomla.Libraries
 * @subpackage  Form
 *
 * @copyright   Copyright (C) 2005 - 2009 Open Source Matters, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Form\FormField;
use Joomla\Registry\Registry;
/**
 * Form Field class for the Joomla Framework.
 *
 * @since  2.5
 */
class JFormFieldT4customcolor extends FormField
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	protected $type = 'T4customcolor';
	protected $layout = 'field.customcolor';

	protected function getInput(){
		$data = [];
		$data['id'] = $this->id;
		$data['name'] = $this->name;
		$data['colors'] = $this->getCustomUsers();
		$data['value'] = json_encode($this->getCustomUsers());
		return \JLayoutHelper::render ($this->layout, $data, T4PATH_ADMIN . '/layouts');
	}

	protected function getLabel(){
		// use this indicator to hide the empty control generated by tplhelper
		return null;
	}
	protected function getCustomUsers(){
		// get base custom colors
		$baseUsercolors = (array) json_decode(T4\Helper\Path::getFileContent('etc/customcolors.json', false), true);
		// local custom colors
		$file = T4PATH_LOCAL . '/etc/customcolors.json';
		$customColors = is_file($file) ? (array) json_decode(file_get_contents($file), true) : [];

		$keys = array_unique(array_merge(array_keys($customColors), array_keys($baseUsercolors)));
		$userColors = [];
		foreach ($keys as $key) {
			$base = !empty($baseUsercolors[$key]) ? $baseUsercolors[$key] : [];
			$local = !empty($customColors[$key]) ? $customColors[$key] : [];
			$status = '';
			$ovr = $loc = $org = false;
			if (!empty($base) && !empty($local) && ($base['color'] != $local['color'] || $base['name'] != $local['name'])) {
				$value = $local;
				$ovr = true;
			} else if (!empty($base)) {
				$value = $base;
				$org = true;
			} else if (!empty($local)) {
				$value = $local;
				$loc = true;
			}
			$status = $ovr || ($loc && $org) ? 'ovr' : ($loc ? 'loc' : 'org');
			$value['status'] = $status;
			$userColors[$key] = $value;
		}
		return $userColors;
	}

}
