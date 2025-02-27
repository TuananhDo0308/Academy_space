<?php

defined('JPATH_PLATFORM') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Layout\LayoutHelper;
use T4Admin\MegaSettings as MegaSettings;

class JFormFieldNavigation extends FormField
{
	/**
	 * The field type.
	 *
	 * @var		string
	 */
	protected $type = 'Navigation';
	protected $layout = 'field.navigation';

	protected function getInput()
	{

		$html = '';
		$paramsTpl = Factory::getApplication()->getTemplate(true)->params;
		if (empty($paramsTpl->get('t4-navigation'))) $paramsTpl->set('t4-navigation', 'default');
		$data_navigataion = json_decode(\T4\Helper\Path::getFileContent('etc/navigation/' . $paramsTpl->get('t4-navigation') . '.json'));
		$rows = (!empty($data_navigataion->mega_settings)) ? json_decode($data_navigataion->mega_settings) : new stdClass();
		$data['id'] = $this->id;
		$data['name'] = $this->name;
		$data['value'] = $this->value;
		$data['data'] = $rows;

		return LayoutHelper::render($this->layout, $data, T4PATH_ADMIN . '/layouts');
	}
	/**
	 * Method to get the field label markup for a spacer.
	 * Use the label text or name from the XML element as the spacer or
	 * Use a hr="true" to automatically generate plain hr markup
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   11.1
	 */
	protected function getLabel()
	{
		// use this indicator to hide the empty control generated by tplhelper
		return null;
	}
	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$doc = Factory::getDocument();
		$doc->addScript(T4PATH_ADMIN_URI . '/assets/js/t4Megamenu.js');
		// add assets
		$allMenu = MegaSettings::getAllItems();
		$script = "var all_menu_item = [] , all_menu_item =" . json_encode($allMenu);
		$doc->addScriptDeclaration($script);
		return parent::setup($element, $value, $group);
	}
}
