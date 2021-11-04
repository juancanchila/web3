<?php

namespace Drupal\ip2country\Commands;

use Consolidation\OutputFormatters\StructuredData\RowsOfFields;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Locale\CountryManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\ip2country\Ip2CountryLookup;
use Drupal\ip2country\Ip2CountryManager;
use Drush\Commands\DrushCommands;

/**
 * Drush 9+ commands for the IP2Country module.
 */
class Ip2CountryCommands extends DrushCommands {

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $stateService;

  /**
   * The date.formatter service.
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The core country_manager service.
   *
   * @var \Drupal\Core\Locale\CountryManagerInterface
   */
  protected $countryManager;

  /**
   * The ip2country lookup service.
   *
   * @var \Drupal\ip2country\Ip2CountryLookup
   */
  protected $ip2countryLookup;

  /**
   * The ip2country database manager.
   *
   * @var \Drupal\ip2country\Ip2CountryManager
   */
  protected $ip2countryManager;

  /**
   * The ip2country logger.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Ip2CountryCommands constructor.
   *
   * @param \Drupal\Core\State\StateInterface $stateService
   *   The state service.
   * @param \Drupal\Core\Datetime\DateFormatterInterface $dateFormatter
   *   The date formatter service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Locale\CountryManagerInterface $countryManager
   *   The core country_manager service.
   * @param \Drupal\ip2country\Ip2CountryLookup $ip2country_lookup
   *   The ip2country lookup service.
   * @param \Drupal\ip2country\Ip2CountryManager $ip2country_manager
   *   The ip2country database manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   The logger.factory service.
   */
  public function __construct(StateInterface $stateService, DateFormatterInterface $dateFormatter, ConfigFactoryInterface $config_factory, CountryManagerInterface $countryManager, Ip2CountryLookup $ip2country_lookup, Ip2CountryManager $ip2country_manager, LoggerChannelFactoryInterface $logger_factory) {
    parent::__construct();
    $this->stateService = $stateService;
    $this->dateFormatter = $dateFormatter;
    $this->configFactory = $config_factory;
    $this->countryManager = $countryManager;
    $this->ip2countryLookup = $ip2country_lookup;
    $this->ip2countryManager = $ip2country_manager;
    $this->logger = $logger_factory->get('ip2country');
  }

  /**
   * Updates the Ip2Country database from a Regional Internet Registry.
   *
   * @param array $options
   *   Registry used to obtain data.
   *
   * @command ip2country:update
   * @aliases ip-update,ip2country-update
   *
   * @option registry
   *   Registry used to obtain data. Can be one of afrnic, apnic, arin, lapnic,
   *   or ripe.
   * @option md5
   *   Validate data integrity with MD5 checksum.
   * @option batch_size
   *   Row insertion batch size. Defaults to '200' rows per insert.
   *
   * @usage drush ip2country:update --registry=ripe
   *   Updates Ip2Country database of ip/country associations.
   * @usage drush ip2country:update --registry=apnic --batch_size=200 --md5
   *   Updates Ip2Country database with a batch size of 200 rows and verifies
   *   the updated data with the MD5 checksum.
   *
   * @validate-module-enabled ip2country
   */
  public function update(array $options = ['registry' => NULL, 'batch_size' => NULL]) {
    $ip2country_config = $this->configFactory->get('ip2country.settings');
    $watchdog = $ip2country_config->get('watchdog');

    if (empty($options['registry'])) {
      $options['registry'] = $ip2country_config->get('rir');
    }
    if (empty($options['md5'])) {
      $options['md5'] = $ip2country_config->get('md5_checksum');
    }
    if (empty($options['batch_size'])) {
      $options['batch_size'] = $ip2country_config->get('batch_size');
    }

    // Tell the user we're working on it ...
    $this->output->write(dt('Updating ... '), FALSE);

    $status = $this->ip2countryManager->updateDatabase(
      (string) $options['registry'],
      (bool) $options['md5_checksum'],
      (int) $options['batch_size']
    );

    if ($status != FALSE) {
      $this->output->writeln(dt('Completed.'));
      $this->output->writeln(dt('Database updated from @registry server. Table contains @rows rows.', [
        '@registry' => mb_strtoupper($options['registry']),
        '@rows' => $status,
      ]));

      // Log update to watchdog, if ip2country logging is enabled.
      if ($watchdog) {
        $this->logger->notice('Drush-initiated database update from @registry server.', [
          '@registry' => mb_strtoupper($options['registry']),
        ]);
      }
    }
    else {
      $this->output->writeln(dt('Failed.'));
      $this->output->writeln(dt('Database update from @registry server FAILED.', [
        '@registry' => mb_strtoupper($options['registry']),
      ]));
      // Log update failure to watchdog, if ip2country logging is enabled.
      if ($watchdog) {
        $this->logger->warning('Drush-initiated database update from @registry server FAILED.', [
          '@registry' => mb_strtoupper($options['registry']),
        ]);
      }
    }
  }

  /**
   * Finds the country associated with the given IP address.
   *
   * @param string $ip_address
   *   The IPV4 address to look up, in dotted-quad notation (e.g. 127.0.0.1).
   *
   * @command ip2country:lookup
   * @aliases ip-lookup,ip2country-lookup
   *
   * @usage drush ip2country:lookup IPV4
   *   Returns a country code associated with the given IP address.
   * @usage drush ip2country:lookup IPV4 --field=name
   *   Returns Country name for the IP address.
   *
   * @table-style default
   * @field-labels
   *   ip_address: IP address
   *   name: Country
   *   country_code_iso2: Country code
   * @default-fields ip_address,name,country_code_iso2
   *
   * @validate-module-enabled ip2country
   *
   * @return \Consolidation\OutputFormatters\StructuredData\RowsOfFields
   *   Returns the country name and two-character ISO 3166 country code
   *   associated with the given IP address.
   */
  public function lookup($ip_address) {
    $country_code = $this->ip2countryLookup->getCountry($ip_address);

    $rows = [];
    if ($country_code == FALSE) {
      $this->output->writeln(dt('IP address not found in the database.'));
    }
    else {
      $country_list = $this->countryManager->getList();
      $country_name = $country_list[$country_code];
      $rows[$ip_address] = [
        'ip_address' => $ip_address,
        'name' => (string) dt($country_name),
        'country_code_iso2' => $country_code,
      ];
    }

    return new RowsOfFields($rows);
  }

  /**
   * Displays the time and RIR of the last database update.
   *
   * @command ip2country:status
   * @aliases ip-status,ip2country-status
   *
   * @usage drush ip2country:status
   *   Returns a country code associated with the given IP address.
   *
   * @validate-module-enabled ip2country
   */
  public function status() {
    $update_time = $this->stateService->get('ip2country_last_update');
    if (!empty($update_time)) {
      $message = dt(
        'Database last updated on @date at @time from @registry server.',
        [
          '@date' => $this->dateFormatter->format($update_time, 'ip2country_date'),
          '@time' => $this->dateFormatter->format($update_time, 'ip2country_time'),
          '@registry' => mb_strtoupper($this->stateService->get('ip2country_last_update_rir')),
        ]
      );
    }
    else {
      $message = dt('Database is empty.');
    }

    $this->output->writeln($message);
  }

}
