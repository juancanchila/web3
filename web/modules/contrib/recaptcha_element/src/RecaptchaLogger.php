<?php

namespace Drupal\recaptcha_element;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Psr\Log\LoggerInterface;
use ReCaptcha\ReCaptcha;
use ReCaptcha\Response as ReCaptchaResponse;

/**
 * Provides a service to log reCAPTCHA responses.
 */
class RecaptchaLogger {

  const ERRORS_LOG_LEVEL_ERROR = [
    Recaptcha::E_BAD_RESPONSE,
    ReCaptcha::E_UNKNOWN_ERROR,
    ReCaptcha::E_CONNECTION_FAILED,
    ReCaptcha::E_INVALID_JSON,
    'invalid-input-secret',
  ];

  protected $logger;

  protected $logSuccesses;

  /**
   * Constructs a RecaptchaLogger object.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   The logger instance to use.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   */
  public function __construct(LoggerInterface $logger, ConfigFactoryInterface $config_factory) {
    $this->logger = $logger;
    $this->logSuccesses = $config_factory->get('recaptcha_element.settings')->get('log_successes');
  }

  /**
   * Logs a recaptcha response.
   *
   * @param \ReCaptcha\Response $response
   *   The reCAPTCHA response to log.
   */
  public function log(ReCaptchaResponse $response) {
    if ($response->isSuccess()) {
      $this->logSuccess($response);
    }
    else {
      $this->logFailure($response);
    }
  }

  /**
   * Logs a successful recaptcha response.
   *
   * @param \ReCaptcha\Response $response
   *   The reCAPTCHA response to log.
   */
  protected function logSuccess(ReCaptchaResponse $response) {
    $this->logger->info('Google reCAPTCHA verification passed for action %action with score %score.', [
      '%action' => $response->getAction(),
      '%score' => $response->getScore(),
    ]);
  }

  /**
   * Logs a failed recaptcha response.
   *
   * @param \ReCaptcha\Response $response
   *   The reCAPTCHA response to log.
   */
  protected function logFailure(ReCaptchaResponse $response) {
    $this->logger->log($this->getLogLevel($response), 'Google reCAPTCHA verification failed for action %action with score %score and errors: @errors.', [
      '%action' => $response->getAction(),
      '%score' => $response->getScore(),
      '@errors' => implode(', ', $response->getErrorCodes()),
    ]);
  }

  /**
   * Determines the log level for a recaptcha response.
   *
   * @param \ReCaptcha\Response $response
   *   The reCAPTCHA response to determine the log level for.
   *
   * @return int
   *   The determined log level.
   */
  public function getLogLevel(ReCaptchaResponse $response): int {
    if ($response->isSuccess()) {
      return RfcLogLevel::INFO;
    }

    $error_codes = $response->getErrorCodes();
    if (array_intersect($error_codes, static::ERRORS_LOG_LEVEL_ERROR)) {
      return RfcLogLevel::ERROR;
    }

    return RfcLogLevel::NOTICE;
  }

}
