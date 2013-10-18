<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\bootstrap;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Tabs renders a Tab bootstrap javascript component.
 *
 * For example:
 *
 * ```php
 * echo Tabs::widget([
 *     'items' => [
 *         [
 *             'label' => 'One',
 *             'content' => 'Anim pariatur cliche...',
 *             'active' => true
 *         ],
 *         [
 *             'label' => 'Two',
 *             'content' => 'Anim pariatur cliche...',
 *             'headerOptions' => [...],
 *             'options' => ['id' => 'myveryownID'],
 *         ],
 *         [
 *             'label' => 'Dropdown',
 *             'items' => [
 *                  [
 *                      'label' => 'DropdownA',
 *                      'content' => 'DropdownA, Anim pariatur cliche...',
 *                  ],
 *                  [
 *                      'label' => 'DropdownB',
 *                      'content' => 'DropdownB, Anim pariatur cliche...',
 *                  ],
 *             ],
 *         ],
 *     ],
 * ]);
 * ```
 *
 * @see http://twitter.github.io/bootstrap/javascript.html#tabs
 * @author Antonio Ramirez <amigo.cobos@gmail.com>
 * @since 2.0
 */
class Tabs extends Widget
{
	/**
	 * @var array list of tabs in the tabs widget. Each array element represents a single
	 * tab with the following structure:
	 *
	 * - label: string, required, the tab header label.
	 * - headerOptions: array, optional, the HTML attributes of the tab header.
	 * - content: array, required if `items` is not set. The content (HTML) of the tab pane.
	 * - options: array, optional, the HTML attributes of the tab pane container.
	 * - active: boolean, optional, whether the item tab header and pane should be visible or not.
	 * - items: array, optional, if not set then `content` will be required. The `items` specify a dropdown items
	 *   configuration array. Each item can hold two extra keys, besides the above ones:
	 *     * active: boolean, optional, whether the item tab header and pane should be visible or not.
	 *     * content: string, required if `items` is not set. The content (HTML) of the tab pane.
	 *     * contentOptions: optional, array, the HTML attributes of the tab content container.
	 */
	public $items = [];
	/**
	 * @var array list of HTML attributes for the item container tags. This will be overwritten
	 * by the "options" set in individual [[items]]. The following special options are recognized:
	 *
	 * - tag: string, defaults to "div", the tag name of the item container tags.
	 */
	public $itemOptions = [];
	/**
	 * @var array list of HTML attributes for the header container tags. This will be overwritten
	 * by the "headerOptions" set in individual [[items]].
	 */
	public $headerOptions = [];
	/**
	 * @var boolean whether the labels for header items should be HTML-encoded.
	 */
	public $encodeLabels = true;


	/**
	 * Initializes the widget.
	 */
	public function init()
	{
		parent::init();
		Html::addCssClass($this->options, 'nav nav-tabs');
	}

	/**
	 * Renders the widget.
	 */
	public function run()
	{
		echo $this->renderItems();
		$this->registerPlugin('tab');
	}

	/**
	 * Renders tab items as specified on [[items]].
	 * @return string the rendering result.
	 * @throws InvalidConfigException.
	 */
	protected function renderItems()
	{
		$headers = [];
		$panes = [];
		foreach ($this->items as $n => $item) {
			if (!isset($item['label'])) {
				throw new InvalidConfigException("The 'label' option is required.");
			}
			$label = $this->encodeLabels ? Html::encode($item['label']) : $item['label'];
			$headerOptions = array_merge($this->headerOptions, ArrayHelper::getValue($item, 'headerOptions', []));

			if (isset($item['items'])) {
				$label .= ' <b class="caret"></b>';
				Html::addCssClass($headerOptions, 'dropdown');

				if ($this->renderDropdown($item['items'], $panes)) {
					Html::addCssClass($headerOptions, 'active');
				}

				$header = Html::a($label, "#", ['class' => 'dropdown-toggle', 'data-toggle' => 'dropdown']) . "\n"
					. Dropdown::widget(['items' => $item['items'], 'clientOptions' => false]);
			} elseif (isset($item['content'])) {
				$options = array_merge($this->itemOptions, ArrayHelper::getValue($item, 'options', []));
				$options['id'] = ArrayHelper::getValue($options, 'id', $this->options['id'] . '-tab' . $n);

				Html::addCssClass($options, 'tab-pane');
				if (ArrayHelper::remove($item, 'active')) {
					Html::addCssClass($options, 'active');
					Html::addCssClass($headerOptions, 'active');
				}
				$header = Html::a($label, '#' . $options['id'], ['data-toggle' => 'tab']);
				$panes[] = Html::tag('div', $item['content'], $options);
			} else {
				throw new InvalidConfigException("Either the 'content' or 'items' option must be set.");
			}

			$headers[] = Html::tag('li', $header, $headerOptions);
		}

		return Html::tag('ul', implode("\n", $headers), $this->options) . "\n"
			. Html::tag('div', implode("\n", $panes), ['class' => 'tab-content']);
	}

	/**
	 * Normalizes dropdown item options by removing tab specific keys `content` and `contentOptions`, and also
	 * configure `panes` accordingly.
	 * @param array $items the dropdown items configuration.
	 * @param array $panes the panes reference array.
	 * @return boolean whether any of the dropdown items is `active` or not.
	 * @throws InvalidConfigException
	 */
	protected function renderDropdown(&$items, &$panes)
	{
		$itemActive = false;

		foreach ($items as $n => &$item) {
			if (is_string($item)) {
				continue;
			}
			if (!isset($item['content'])) {
				throw new InvalidConfigException("The 'content' option is required.");
			}

			$content = ArrayHelper::remove($item, 'content');
			$options = ArrayHelper::remove($item, 'contentOptions', []);
			Html::addCssClass($options, 'tab-pane');
			if (ArrayHelper::remove($item, 'active')) {
				Html::addCssClass($options, 'active');
				Html::addCssClass($item['options'], 'active');
				$itemActive = true;
			}

			$options['id'] = ArrayHelper::getValue($options, 'id', $this->options['id'] . '-dd-tab' . $n);
			$item['url'] = '#' . $options['id'];
			$item['linkOptions']['data-toggle'] = 'tab';

			$panes[] = Html::tag('div', $content, $options);

			unset($item);
		}
		return $itemActive;
	}
}
