<?php

namespace Drupal\barcodes\Template;

use Com\Tecnick\Barcode\Barcode as BarcodeGenerator;
use Drupal\Core\Utility\Token;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Class Barcode.
 *
 * @package Drupal\barcodes\Template
 */
class Barcode extends AbstractExtension {

  /**
   * The token service.
   *
   * @var \Drupal\Core\Utility\Token
   */
  protected $token;

  /**
   * Constructs a Barcode Twig extension.
   *
   * @param \Drupal\Core\Utility\Token $token
   *   The token service.
   */
  public function __construct(Token $token) {
    $this->token = $token;
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return 'barcode';
  }

  /**
   * {@inheritdoc}
   */
  public function getFilters() {
    return [
      new TwigFilter(
        'barcode',
        [
          $this,
          'filterBarcode',
        ],
        [
          'is_safe' => [
            'html',
          ],
        ]
      ),
    ];
  }

  /**
   * Barcode filter.
   *
   * @param string $value
   *   The string that should be formatted as a barcode.
   * @param string $type
   *   The barcode type.
   * @param string $color
   *   The barcode color.
   * @param int $height
   *   The barcode height.
   * @param int $width
   *   The barcode width.
   * @param int $padding_top
   *   The barcode top padding.
   * @param int $padding_right
   *   The barcode right padding.
   * @param int $padding_bottom
   *   The barcode bottom padding.
   * @param int $padding_left
   *   The barcode left padding.
   *
   * @return string
   *   The barcode markup to display.
   *
   * @throws \Com\Tecnick\Barcode\Exception
   */
  public function filterBarcode($value, $type = 'QRCODE', $color = '#000000', $height = 100, $width = 100, $padding_top = 0, $padding_right = 0, $padding_bottom = 0, $padding_left = 0) {
    $value = (string) $value;

    $generator = new BarcodeGenerator();
    $value = $this->token->replace($value);

    $barcode = $generator->getBarcodeObj(
      $type,
      $value,
      $width,
      $height,
      $color,
      [
        $padding_top,
        $padding_right,
        $padding_bottom,
        $padding_left,
      ]
    );
    return $barcode->getSvgCode();
  }

}
