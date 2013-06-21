<?php
namespace Sheldon;
use Symfony\Component\Console\Formatter;
use Symfony\Component\Console\Helper\TableHelper;
use Zend\Console as ZendConsole;

/**
 * Simple stream output class.
 * Uses the Formatter package from Symfony2 to parse xml-like style tags within text.
 * Uses the Console\Adapter package from ZF2 to implement more basic colorization and other drawing.
 *
 * @package Sheldon
 */
class StreamWriter
{

  /**
   * @var Formatter\OutputFormatterInterface
   */
  protected $formatter;

  /**
   * The stream resource. Defaults to STDOUT
   * @var resource
   */
  protected $stream;

  /**
   * The ZF2 Console Adapter for platform agnostic console I/O
   * @var \Zend\Console\Adapter\AdapterInterface
   * @link http://framework.zend.com/apidoc/2.2/classes/Zend.Console.Adapter.AdapterInterface.html
   */
  protected $adapter = null;

  /**
   * Should output be colorized? Either pass a choice to the constructor or leave null for a sniff test to determine
   * colorization support.
   * @var bool
   */
  protected $color_support = null;

  /**
   * An instance of Shim class that implements our hack of the Symfony2 OutputInterface
   * @var Shim
   */
  protected $output_shim = null;


  public function __construct($stream = null, $formatter = null, $adapter = null, $shim = null, $color_support = null)
  {
    $this->stream         = $stream    ?: STDOUT;
    $this->formatter      = $formatter ?: new Formatter\OutputFormatter();
    $this->adapter        = $adapter   ?: ZendConsole\Console::getInstance();
    $this->output_shim    = $shim      ?: new Shim($this);
    $this->color_support  = ($color_support !== null) ? $color_support : $this->has_color_support();

    $this->formatter->setDecorated($this->color_support);
    $this->formatter = static::formatter($this->formatter);
  }

  /**
   * @return StreamWriter
   */
  public function __invoke()
  {
    call_user_func_array(array($this, 'styled'), func_get_args());
    return $this;
  }

  public function __get($var)
  {
    if (in_array($var, array('formatter')))
    {
      return $this->{$var};
    }

    return null;
  }

  public function table(TableHelper $table)
  {
    $table->render($this->output_shim);
    return $this;
  }

  /**
   * Output the provided 2-dimensional data array as columns of text.
   * Cannot use the TableHelper because, ironically, it does not parse <style> tags.
   * @param array $data
   * @param TableHelper $table
   */
  public function column(Array $data, $column_padding = 5)
  {
    if (empty($data))
    {
      return $this;
    }

    $widths = array();
    foreach($data as $row)
    {
      foreach($row as $column => $text)
      {
        $widths[$column] = @max($widths[$column], strlen($text));
      }
    }

    $columns = max(array_keys($widths));

    $this->eol();
    foreach($data as $row)
    {
      foreach ($row as $column => $text)
      {
        $this->styled(static::pad($text, $widths[$column] + $column_padding));
      }
      $this->eol();
    }

    return $this;
  }

  /**
   * When you just want to write some text with a color or bg color, without using embedded style tags in your text.
   * Uses the Zend Framework colorization code.
   * Note: Text will appear monochrome if xterm coloring is not supported by your termina.
   * Note: Colors should be passed-in from the Sheldon\Colors enum class.
   *
   * @param $text
   * @param null $color
   * @param null $bgcolor
   */
  public function colored($text, $color = null, $bgcolor = null)
  {
    if ($this->color_support)
    {
      $text = $this->adapter->colorize($text, $color, $bgcolor);
    }

    $this->flush($text);
    return $this;
  }

  /**
   * Write stylized text.
   * Note: You can simplify this expression by invoking $this as a function.
   * The styles are parsed using the Symfony2 Console Formatter package.
   *
   * @example $this("<question>Desired Path?</question>");
   * @example $this->styled("<question>Desired Path?</question>");
   *
   * @link http://symfony.com/doc/2.0/components/console/introduction.html
   *
   * @param $text
   * @param int  $color
   * @param bool $newline
   */
  public function styled($text, $surround_with = '')
  {
    if ($surround_with)
    {
      $text = $surround_with . $text . str_replace('<', '</', $surround_with);
    }

    $text = $this->formatter->format($text);
    $this->flush($text);
    return $this;
  }

  /**
   * Write the given $text with an appended newline character.
   * @param int $count
   * @return $this
   */
  public function eol($count = 1)
  {
    $this->flush(str_repeat(PHP_EOL, $count));
    return $this;
  }

  protected function flush($text)
  {
    @fwrite($this->stream, $text);
    @fflush($this->stream);
  }

  /**
   * Determine if the stream we're writing to supports colorized output.
   * Logic cribbed from Symfony2 StreamOutput class.
   * @return bool
   */
  public function has_color_support()
  {
    static $supported = null;

    if ($supported === null)
    {
      if (DIRECTORY_SEPARATOR == '\\')
      {
        $supported = (false !== getenv('ANSICON') || 'ON' === getenv('ConEmuANSI'));
      }
      else
      {
        $supported = (function_exists('posix_isatty') && @posix_isatty($this->stream));
      }
    }

    return $supported;
  }

  /**
   * Return an OutputFormatter instance with a canonical configuration
   * @param Formatter\OutputFormatter $instance
   * @return Formatter\OutputFormatter
   */
  public static function formatter(Formatter\OutputFormatter $instance = null)
  {
    if (!$instance)                       $instance = new Formatter\OutputFormatter();
    if (!$instance->hasStyle('command'))  $instance->setStyle('command',   new PosixStyle(Colors::BLUE, null));
    if (!$instance->hasStyle('optional')) $instance->setStyle('optional',  new PosixStyle(Colors::GRAY));
    if (!$instance->hasStyle('notice'))   $instance->setStyle('notice',    new PosixStyle(Colors::BLUE, null, array('bold')));
    if (!$instance->hasStyle('header'))   $instance->setStyle('header',    new PosixStyle(Colors::BLUE, null));
    if (!$instance->hasStyle('warning'))  $instance->setStyle('warning',   new PosixStyle(Colors::BLACK, Colors::YELLOW));
    if (!$instance->hasStyle('success'))  $instance->setStyle('success',   new PosixStyle(Colors::BLACK, Colors::GREEN));

    // Builtins:
    // $this->setStyle('error',     new OutputFormatterStyle('white', 'red'));
    // $this->setStyle('info',      new OutputFormatterStyle('green'));
    // $this->setStyle('comment',   new OutputFormatterStyle('yellow'));
    // $this->setStyle('question',  new OutputFormatterStyle('black', 'cyan'));

    return $instance;
  }

  /**
   * Takes a basic guess on whether or not the supplied $text includes style tags eg <error> or <fg=red>. Looks simply
   * for the presence of an opening and closing tag.
   * @param $text
   * @return bool
   */
  public static function is_styled($text)
  {
    return (bool) preg_match('|.*<[A-Za-z0-9=-]+>.+</[A-Za-z0-9=-]+>.*|', $text);
  }

  /**
   * Add display-padding to a formatted string
   * @param $text
   * @param $len
   * @param $char
   * @return string
   */
  public static function pad($text, $len, $char = ' ', $type = STR_PAD_RIGHT, $formatter = null)
  {
    // Create an instance of the Formatter object that's configured to strip style tags.
    static $formatter = null;
    if ($formatter === null)
    {
      $formatter = static::formatter();
      $formatter->setDecorated(false);
    }

    // Pad a style-stripped version of the string, determine the padding applied, then apply
    // it to the formatted version that will actually be used in the application
    $plain  = $formatter->format($text);
    $padded = str_pad($plain, $len, $char, $type);
    $delta  = strlen($padded) - strlen($plain);

    switch($type)
    {
      case STR_PAD_RIGHT: return $text . str_repeat($char, $delta);
      case STR_PAD_LEFT:  return str_repeat($char, $delta) . $text;
      case STR_PAD_BOTH:  return str_repeat($char, $delta / 2) . $text . str_repeat($char, $delta / 2);
    }

    return $text;
  }

  /**
   * If the given $text does not end in $count white space character(s), append them
   * @param $text
   * @param int $spaces
   */
  public static function rspace($text, $count = 1)
  {
    if (preg_match('/\s/', substr($text, -1*$count)) == 0)
    {
      $text = rtrim($text) . str_repeat(' ', $count);
    }

    return $text;
  }

}