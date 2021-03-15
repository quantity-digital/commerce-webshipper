<?php

namespace QD\commerce\webshipper;

use Craft;
use craft\commerce\elements\db\OrderQuery;
use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use craft\commerce\models\ShippingMethod;
use craft\events\DefineBehaviorsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\TemplateEvent;
use craft\helpers\UrlHelper;
use craft\services\Elements;
use craft\services\Plugins;
use craft\web\twig\variables\CraftVariable;
use craft\web\UrlManager;
use craft\web\View;
use QD\commerce\webshipper\behaviors\OrderBehavior;
use QD\commerce\webshipper\behaviors\OrderQueryBehavior;
use QD\commerce\webshipper\behaviors\ShippingMethodBehavior;
use QD\commerce\webshipper\helpers\Connector;
use QD\commerce\webshipper\models\Settings;
use QD\commerce\webshipper\plugin\Services;
use QD\commerce\webshipper\variables\WebshipperVariable;
use yii\base\Event;

class Webshipper extends \craft\base\Plugin
{

	use Services;

	// Static Properties
	// =========================================================================

	public static $plugin;

	/**
	 * @var bool
	 */
	public static $commerceInstalled = false;

	// Public Properties
	// =========================================================================

	/**
	 * @inheritDoc
	 */
	public $schemaVersion = '1.0.2';
	public $hasCpSettings = true;
	public $hasCpSection = true;

	// Public Methods
	// =========================================================================

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		self::$plugin = $this;

		self::$commerceInstalled = class_exists(Commerce::class);

		// Install event listeners
		$this->setPluginComponents();
		$this->registerVariables();
		$this->installEventListeners();
		$this->registerCommerceEventListeners();
	}

	public function getPluginName()
	{
		return 'Webshipper';
	}

	protected function installEventListeners()
	{
		Event::on(
			Plugins::class,
			Plugins::EVENT_AFTER_LOAD_PLUGINS,
			function () {
				$request = Craft::$app->getRequest();

				if ($request->getIsSiteRequest() && !$request->getIsConsoleRequest()) {
					$this->installSiteEventListeners();
				}

				if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
					$this->installCpEventListeners();
				}
			}
		);
	}

	public function getSettingsResponse()
	{
		Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('webshipper/settings'));
	}

	// Protected Methods
	// =========================================================================

	protected function createSettingsModel()
	{
		return new Settings();
	}


	protected function installSiteEventListeners()
	{
		Event::on(
			UrlManager::class,
			UrlManager::EVENT_REGISTER_SITE_URL_RULES,
			function (RegisterUrlRulesEvent $event) {
				$event->rules = array_merge($event->rules, [
					'craftapi/webshipper/v1/webhook/shipment/created' => 'commerce-webshipper/webhook/shipment-created',
					'craftapi/webshipper/v1/webhook/order/deleted' => 'commerce-webshipper/webhook/order-deleted',
					'craftapi/webshipper/droppoint/locator' => 'commerce-webshipper/frontend/droppoint-locator'
				]);
			}
		);
	}

	protected function installCpEventListeners()
	{
		// Add shipments tab to order edit page.
		Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
			if ($event->template === 'commerce/orders/_edit') {
				$event->variables['tabs'][] = [
					'label' => 'Shipping',
					'url' => '#shippingTab',
					'class' => null,
				];
			}
		});

		// Uses order edit template hook to inject order shipments.
		Craft::$app->view->hook('cp.commerce.order.edit', function (&$context) {
			/* @var Order $order */
			$order = $context['order'];
			$shipmentLines = [];

			/* @var LineItem $lineItem */
			foreach ($order->getLineItems() as $lineItem) {
				$shippableQty = $this->getShipmentLines()->getShippableQty($lineItem, $limitToStock = true);
				$maxshippableQty = $this->getShipmentLines()->getShippableQty($lineItem);

				$shipmentLines[] = [
					'id' => $lineItem->id,
					'title' => $lineItem->description ?? $lineItem->getDescription(),
					'qty' => $shippableQty,
					'maxQty' => $maxshippableQty
				];
			}

			$context['shipmentLines'] = $shipmentLines;

			return Craft::$app->view->renderTemplate('commerce-webshipper/order/shipments', $context);
		});

		Craft::$app->view->hook('cp.commerce.shippingMethods.edit.content', function (&$context) {
			$shippingMethod = $context['shippingMethod'];
			$webshipper = new Connector();
			$context['webshipperRateId'] = $shippingMethod->getWebshipperRateId();
			$webshipperRates = $webshipper->getShippingRates();

			$rates = [
				null => '---'
			];

			foreach ($webshipperRates as $rate) {
				$rates[$rate['id']] = $rate['attributes']['name'];
			}

			$context['webshipperRates'] = $rates;
			return Craft::$app->view->renderTemplate('commerce-webshipper/shippingmethod/edit', $context);
		});

		/**
		 * Product edit page - add webshipper tab
		 * TODO Implement product location + (tarif number + Country of Origin for automatic customs)
		 */
		/**
		 * Product edit page - add webshipper tab
		 */
		// Event::on(View::class, View::EVENT_BEFORE_RENDER_PAGE_TEMPLATE, function (TemplateEvent $event) {
		// 	if ($event->template === 'commerce/products/_edit') {
		// 		$event->variables['tabs'][] = [
		// 			'label' => 'Webshipper',
		// 			'url' => '#webshipperTab',
		// 			'class' => null,
		// 		];
		// 	}
		// });

		Craft::$app->view->hook('cp.commerce.product.edit.content', function (&$context) {
			return Craft::$app->view->renderTemplate('commerce-webshipper/product/edit', $context);
		});

		/**
		 * Register webshipper plugin admin routes
		 */
		Event::on(UrlManager::class, UrlManager::EVENT_REGISTER_CP_URL_RULES, function (RegisterUrlRulesEvent $event) {
			$event->rules = array_merge($event->rules, [
				'webshipper/settings' => 'commerce-webshipper/plugin/settings',
			]);
		});
	}

	// Private Methods
	// =========================================================================

	private function registerVariables()
	{
		Event::on(CraftVariable::class, CraftVariable::EVENT_INIT, function (Event $event) {
			$event->sender->set('webshipper', WebshipperVariable::class);
		});
	}

	private function registerCommerceEventListeners()
	{
		/**
		 * Queue triggers
		 */
		Event::on(Order::class, Order::EVENT_AFTER_COMPLETE_ORDER, [$this->getOrders(), 'addSyncOrderQue']);
		Event::on(Elements::class, Elements::EVENT_AFTER_DELETE_ELEMENT, [$this->getOrders(), 'addDeleteOrderQue']);

		/**
		 * Shippingmethod element behavoiours
		 */
		Event::on(
			ShippingMethod::class,
			ShippingMethod::EVENT_DEFINE_BEHAVIORS,
			function (DefineBehaviorsEvent $e) {
				$e->behaviors['commerce-webshipper.attributes'] = ShippingMethodBehavior::class;
			}
		);

		/**
		 * Order element behaviours
		 */
		Event::on(
			Order::class,
			Order::EVENT_DEFINE_BEHAVIORS,
			function (DefineBehaviorsEvent $e) {
				$e->behaviors['commerce-webshipper.attributes'] = OrderBehavior::class;
			}
		);

		Event::on(
			OrderQuery::class,
			OrderQuery::EVENT_DEFINE_BEHAVIORS,
			function (DefineBehaviorsEvent $e) {
				$e->behaviors['commerce-webshipper.queryparams'] = OrderQueryBehavior::class;
			}
		);
	}
}
