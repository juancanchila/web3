<?php

namespace Drupal\ip2country\Plugin\Condition;

use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\ip2country\Ip2CountryLookupInterface;
use Drupal\rules\Core\RulesConditionBase;
use Drupal\user\UserDataInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'User is in country' condition.
 *
 * @Condition(
 *   id = "ip2country_user_country",
 *   label = @Translation("User is in country (based on IP address)"),
 *   description = @Translation("Uses the ip2country module to determine if the user is located in one of the selected countries."),
 *   category = @Translation("User"),
 *   context_definitions = {
 *     "countries" = @ContextDefinition("string",
 *       label = @Translation("Countries"),
 *       assignment_restriction = "input",
 *       list_options_callback = "countryOptions",
 *       multiple = TRUE,
 *       required = TRUE
 *     )
 *   }
 * )
 */
class UserInCountry extends RulesConditionBase implements ContainerFactoryPluginInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The user.data service.
   *
   * @var \Drupal\user\UserDataInterface
   */
  protected $userData;

  /**
   * The core country_manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The ip2country.lookup service.
   *
   * @var \Drupal\ip2country\Ip2CountryLookupInterface
   */
  protected $ip2countryLookup;

  /**
   * The corresponding request.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs a UserInCountry object.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Session\AccountInterface $currentUser
   *   The current user.
   * @param \Drupal\user\UserDataInterface $userData
   *   The user.data service.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The core country_manager service.
   * @param \Drupal\ip2country\Ip2CountryLookupInterface $ip2countryLookup
   *   The Ip2Country lookup service manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, AccountInterface $currentUser, UserDataInterface $userData, CountryManagerInterface $countryManager, Ip2CountryLookupInterface $ip2countryLookup, RequestStack $requestStack) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->currentUser = $currentUser;
    $this->userData = $userData;
    $this->countryManager = $countryManager;
    $this->ip2countryLookup = $ip2countryLookup;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('current_user'),
      $container->get('user.data'),
      $container->get('country_manager'),
      $container->get('ip2country.lookup'),
      $container->get('request_stack')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function summary() {
    return $this->t('User IP is in Country');
  }

  /**
   * Returns an array of country options.
   *
   * @return array
   *   An array of 2-character country codes keyed by country name.
   */
  public function countryOptions() {
    return $this->countryManager->getList();
  }

  /**
   * Evaluates if the user has an IP address in one of the selected countries.
   *
   * @param array $countries
   *   Array of 2-character country codes.
   *
   * @return bool
   *   TRUE if the user has an IP in one of the given countries.
   */
  protected function doEvaluate(array $countries = []) {
    $userData = $this->userData->get('ip2country', $this->currentUser->id(), 'country_iso_code_2');
    if (isset($userData)) {
      // Use the country stored in the $user object.
      $countryCode = $userData;
    }
    else {
      // Determine the user's country based on IP address of the page request.
      $ip = $this->requestStack->getCurrentRequest()->getClientIp();
      $countryCode = $this->ip2countryLookup->getCountry($ip);
    }

    return in_array($countryCode, $countries);
  }

}
