<?php
namespace Sheldon;
use Zend\Console\Getopt;

/**
 * Extend this to build a commandline user-interface with
 * 1. Optional Commandline Args
 * 2. Required Commandline Args, with enforcement
 * 3. Optional Positional Args
 * 3. A humanized Usage block with optional detailed man instructions
 *
 * Uses ZF Getopt class. Proxies unhandled method calls through to the underlying Getopt object.
 */
class Cli
{

  /**
   * @var Getopt
   */
  protected $cli;

  /**
   * If your command accepts required parameters, define them here, formatted for the ZendFramework Getopt library.
   * You'll have access to passed arguments via the $cli property, or by using the $this->option('name') helper
   * @link http://framework.zend.com/manual/1.12/en/zend.console.cli.rules.html
   * @var array
   */
  protected $required = array();

  /**
   * If your command accepts optional parameters, define them here, formatted for the ZendFramework Getopt library.
   * You'll have access to passed arguments via the $cli property, or by using the $this->option('name') helper
   * @link http://framework.zend.com/manual/1.12/en/zend.console.cli.rules.html
   * @var array
   */
  protected $optional = array();

  /**
   * Any built-in, default options. All are optional by design. Formatted for the ZendFramework Getopt library.
   * You'll have access to passed arguments via the $cli property, or by using the $this->option('name') helper
   * @link http://framework.zend.com/manual/1.12/en/zend.console.cli.rules.html
   * @var array
   */
  protected $builtin = array(
    'help|h'  => 'Display Usage Information'
  );

  /**
   * A usage-block-formatted text description of any arguments used by this app that are not defined in the $required
   * or $optional array. For example, positional arguments.
   * @var Array
   */
  protected $arguments = array();

  /**
   * The brief description of the app, as used in the Usage block, etc
   * @var
   */
  protected $description;

  /**
   * Text instructions printed below options in full usage-block help messages. Each item in the array is a paragraph
   * or section. If your array has a string key, it's printed as a section header.
   * @example $instructions = array('Interactive Mode' => 'Running in interactive mode blah blah blah...');
   * @var array
   */
  protected $instructions = array();


  public function __get($var)
  {
    if (in_array($var, array('cli', 'required', 'optional', 'description', 'arguments')))
    {
      return $this->{$var};
    }

    return null;
  }

  /**
   * Parse a single item from the $optional or $required options arrays into a human readable string.
   * @param $option
   * @example 'verbose|v-s' to '-v|--verbose [string]'
   * @param bool $required    Is this a required param?
   */
  public static function humanize($option, $required = true)
  {
    $legend = array (
      's' => 'string',
      'w' => 'value',
      'i' => 'integer',
      '#' => 'numeric flag',
      '-' => '[',
      '=' => '<'
    );

    // Option keys have an optional validatoion indicator that starts with = or -.
    @list($keys, $validator) = preg_split('/([-=][swi#])/', $option, -1, PREG_SPLIT_DELIM_CAPTURE);

    // Prefix the option key names with the appopriate sigil (- or --)
    $option = array();
    foreach(explode('|', $keys) as $part)
    {
      $option[] = (strlen($part) == 1) ? "-{$part}" : "--{$part}";
    }
    rsort($option);
    $option = implode('|', $option);

    // Expand simple validator expressions like '-s' into meaningful representations, in that case '[string]'
    if ($validator)
    {
      $validator = strtr($validator, $legend);
      $validator = ($validator[0] == '<') ? "{$validator}>" : "{$validator}]";
      $option = "{$option} {$validator}";
    }

    // If it's optional, surround it in square brackets
    if ($required)
    {
      return $option;
    }

    return "[{$option}]";
  }

  /**
   * Parse a single item from the $optional or $required options arrays into a key we can pass to the Getopt object
   * to retreive a specific value. If an option-key has aliases, eg, v|vbr|verbose it'll return the first "longopt"
   * it encounters (vbr in this case) but that's fine b/c Getopt handles aliases how you'd expect it to.
   * @param $option
   * @return null
   */
  public static function key($option)
  {
    $option = substr($option, 0, -2);
    foreach(explode('|', $option) as $part)
    {
      if (strlen($part) > 1)
      {
        return $part;
      }
    }

    return null;
  }

  /**
   * Prints a Usage block
   * @param null $message
   * @param null $writer
   */
  public function usage($message = null, StreamWriter $writer = null)
  {
    $writer = $writer ?: Application::instance()->write;

    $message  = ($message) ?: $this->description;
    $usage    = $this->toArray();

    if (!$usage['usage'] && !$usage['options'] && !$this->instructions)
    {
      return;
    }

    if ($message) $writer($message, '<notice>')->eol();
    $writer('Usage: ' . $usage['usage'])->eol();
    $writer->column($usage['options'])->eol();

    if ($this->instructions)
    {
      foreach($this->instructions as $header => $value)
      {
        if (is_string($header))
        {
          $writer($header, '<header>')->eol();
        }

        $writer($value)->eol();
      }
    }
  }

  /**
   * Return a named option.
   * @param $key
   * @return mixed
   */
  public function option($key)
  {
    return $this->cli->getOption($key);
  }

  /**
   * Retrieve a named argument. Only arguments listed in the $arguments property can be accessed this way. For undefined
   * arguments, call $this->$cli->getRemainingArgs(). This will match defined args to input args positionally.
   * @example The first input arg is matched to the first key in the $arguments array.
   * @param $name
   */
  public function argument($name)
  {
    $args       = $this->cli->getRemainingArgs();
    $arg_count  = count($args);
    $definition = $this->arguments;
    $def_count  = count($definition);

    if ($arg_count < 1)
    {
      return null;
    }

    if ($def_count > $arg_count)
    {
      $definition = array_slice($definition, 0, $arg_count, true);
    }

    if ($def_count < $arg_count)
    {
      $args = array_slice($args, 0, $def_count, true);
    }

    $named_args = array_combine(array_keys($definition), array_values($args));
    return @ ($named_args[$name]) ?: null;
  }

  /**
   * Parse the $command and $options properties into a sensible usage message. Returns an associative array, with keys:
   * usage    = A one-line usage statement
   * options  = An array of detailed options information including any available text description
   * @return Array
   */
  public function toArray()
  {
    // The "short" format is displayed in the top-line "Usage: foo [-v|--veee <blah>]" block. The "long" format is
    // displayed in the 1-line-per-option help page for the command.
    $short_to_long = function($string) {
      $string = preg_replace('/^\[([^]]+)\]/', '$1', $string);
      $string = str_replace('|', ', ', $string);
      return " $string";
    };

    $arguments = $optional = $required = $combined = array();


    foreach($this->arguments as $argument => $description)
    {
      $string       = sprintf('[%s]', ucwords($argument));
      $optional[]   = $string;
      $combined[]   = array($short_to_long($string), $description);
    }

    foreach ($this->required as $option => $description)
    {
      $string     = static::humanize($option, true);
      $required[] = $string;
      $combined[] = array($short_to_long($string), $description);
    }

    foreach ($this->optional + $this->builtin as $option => $description)
    {
      $string     = static::humanize($option, false);
      $optional[] = $string;
      $combined[] = array($short_to_long($string), $description);
    }

    if ($optional = implode(' ', $optional))
    {
      $optional = "<optional>{$optional}</optional>";
    }

    return array (
      'usage'   => trim(sprintf('%s %s %s', $this->command, implode(' ', $required), $optional)),
      'options' => $combined
    );
  }

  /**
   * Create a new Getopt object and validate the existence of any options defined in the $required property.
   * @param null $argv  An array or string of commandline arguments. If not provided, Getopt will default to using $_SERVER['argv']
   */
  protected function setup_cli($argv = null)
  {
    // If a $cli param was set, treat as dependency injection and use it.
    if (!$this->cli)
    {
      $this->cli = new Getopt(null);
      $this->cli->addRules($this->required + $this->optional + $this->builtin);
      $this->cli->setOption(Getopt::CONFIG_NUMERIC_FLAGS,         true);  // Allow flags like -100, eg $ tail -100 output.log
      $this->cli->setOption(Getopt::CONFIG_CUMULATIVE_PARAMETERS, true);  // Allow array params, eg $ foo -s "key1=val1" -s "key2=val2"
      $this->cli->setOption(Getopt::CONFIG_FREEFORM_FLAGS,        true);  // Allow flags that have not been predefined in the $optional and $required array
    }

    // If an $argv param was passed, use it. The first value in the array is treated as the command and ignored.
    if (!empty($argv))
    {
      //$argv = (is_array($argv)) ? $argv : explode(' ', $argv);
      if (!is_array($argv))
      {
        // Split the string on a space, maintaining single and double quoted substrings
        // For simplicity, this regex includes the quotation marks in the matched string.
        // Far more expressive to remove them afterwards than to add a lot of complexity to the regex.
        $pattern = "/('.*?'|\".*?\"|\S+)/";
        preg_match_all($pattern, $argv, $matches);
        $argv = $matches[0];

        // Now do the removal of vestigal quote marks. Only strip once.
        // For example turn "'hi mom'" into 'hi mom' (not plain old: hi mom)
        foreach($argv as &$arg)
        {
          $orig = $arg;
          foreach(array('"', "'") as $mark)
          {
            $pattern = sprintf('/%1$s(.*)%1$s/', $mark);
            $arg = preg_replace($pattern, '$1', $arg);
            if ($arg != $orig) break;
          }
        }
      }

      $this->cli->setArguments(array_slice($argv, 1));
    }

    foreach(array_keys($this->required) as $option)
    {
      $key = $this->key($option);
      if (empty($key) || $this->cli->getOption($key) === false)
      {
        $this->usage("<error>Required Parameter Missing: {$key}</error>");
      }
    }
  }
}