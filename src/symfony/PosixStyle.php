<?php
namespace Sheldon;

/**
 * Extend the Symfony OutputFormatter with custom color codes.
 * The way the underlying OutputFormatterStyle class works, even though we've not changed the Background and Options
 * arrays we need to copy them here from the base class. This is because they're static and the underlying implementation
 * is "private" access and the underlying class accesses them via static::$availableBackgroundColors (for example).
 */
class PosixStyle extends \Symfony\Component\Console\Formatter\OutputFormatterStyle
{
  protected static $availableForegroundColors = array(
    'black'         => 30,
    'red'           => 31,
    'green'         => 32,
    'yellow'        => 33,
    'blue'          => 34,
    'magenta'       => 35,
    'cyan'          => 36,
    'white'         => 37,
    'gray'          => '1;30',
    'light_red'     => '1;31',
    'light_green'   => '1;32',
    'light_yellow'  => '1;33',
    'light_blue'    => '1;34',
    'light_magenta' => '1;35',
    'light_cyan'    => '1;36',
    'light_white'   => '1;37',
  );

  protected static $availableBackgroundColors = array(
    'black'     => 40,
    'red'       => 41,
    'green'     => 42,
    'yellow'    => 43,
    'blue'      => 44,
    'magenta'   => 45,
    'cyan'      => 46,
    'white'     => 47
  );

  protected static $availableOptions = array(
    'bold'          => 1,
    'underscore'    => 4,
    'blink'         => 5,
    'reverse'       => 7,
    'conceal'       => 8
  );

  public function __construct($foreground = null, $background = null, array $options = array())
  {
    $foreground = is_numeric($foreground) ? Colors::map($foreground) : $foreground;
    $background = is_numeric($background) ? Colors::map($background) : $background;
    parent::__construct($foreground, $background, $options);
  }



}