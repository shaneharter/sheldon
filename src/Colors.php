<?php
namespace Sheldon;
use Zend\Console\ColorInterface;

/**
 * Inherits Interface Constants from the ZF2 ColorInterface
 * Class Colors
 */
final class Colors implements ColorInterface
{
  /**
   * The integer keys here map to the color constants in ColorInterface (and inherited into this class).
   * Used to convert the ZendFramework numeric color identifiers to the Symfony2 string identifiers.
   * @var array
   */
  public static $map = array (
    1  => 'black',
    2  => 'red',
    3  => 'green',
    4  => 'yellow',
    5  => 'blue',
    6  => 'magenta',
    7  => 'cyan',
    8  => 'white',
    9  => 'gray',
    10 => 'light_red',
    11 => 'light_green',
    12 => 'light_yellow',
    13 => 'light_blue',
    14 => 'light_magenta',
    15 => 'light_cyan',
    16 => 'light_white',
  );

  /**
   * Map values from the ColorInterface to the named-values used for colors in the Symfony compnents we use
   * @param $number
   */
  public static function map($number)
  {
    return static::$map[$number];
  }
}