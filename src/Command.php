<?php

namespace Sheldon;
use Zend\Console\Getopt;

/**
 * Represents a single command that can be handled from within the Application.
 * Commands are grouped in some way you get to decide into Context objects. Context objects are structured as a linked
 * list and the Application object contains a reference to the last element on the list.
 *
 * When a user inputs a command it is sent to that first Context object, where it will try to match it against all
 * of its Command objects, passing the normalized user input to Command::match(). If no match is found, and if that
 * Context links to another, it will walk down the list to the next node and attempt a match() there. The Application
 * class implements a list sentinel that also contains the built-in Application commands.
 *
 * The takeaway here is that this pattern allows for things like:
 * 1) Later contexts can overload commands from earlier ones. For example as you build additional state in layered
 *    Context objects your commands could require fewer params. Think of how the MySQL client will error if start it
 *    and you run a query that doesn't specify a DB name. However once you run the "use" command, it will create a new
 *    context that has the DB name saved in the state. Now you can overload your earlier query command to allow for
 *    queries that omit a fully-qualified DB name.
 *
 * 2) A context can be added for opertaions on a specific User account and that user_id is stored in the state. And at
 *    any time a, say, "select_user 1234" can be captured that drops that first user context and replaces it with one
 *    containing the new user_id=1234 state.
 *
 * @package Sheldon
 */
abstract class Command extends Cli
{
  /**
   * The Regular Expression used to match user input to this command
   * Note: By default, all input is cast to lowercase.
   *
   * Note: If the shell command requires arguments, it's best to define those args as appropriate in either the
   * $optional or $required array. Write your $pattern regex to match the command at the beginning of the input string,
   * ignoring the params. While you can certainly write a regex to parse-out any options, and then access them in the
   * $matches array, it's far better in the long run to use the Getopt object that's provided in the $cli property
   *
   * @var string
   * @example '/^ls\s*?/i'   Matches the "ls" command at the beginning of the string, with an optional space.
   */
  protected $pattern;

  /**
   * Set any regex flags that will be used when matching this Command to user input
   * @var string
   */
  protected $pattern_flags = 'i';

  /**
   * The short command example used in help dialog. This should essentially match the regex $pattern. If you're using
   * regex only to match the command name leaving Getopt to do the rest, just put the command name here.
   *
   * Note:
   * If you're using regex to parse out required or optional params:
   * 1) Don't do that. Use the Getopt object.
   * 2) Write them in a usage-block-esque way to the $this->arguments
   *
   * @var string
   * @example 'ls [path]'
   * @example 'help'
   * @example 'omg [--wtf <foo>]'
   */
  protected $command;

  /**
   * You may want to hide some functionality from the `help` index.
   * When $hidden=true, the command will be skipped in the help listing
   * @var bool
   */
  protected $hidden = false;

  /**
   * The extended help text for this command. Displayed when the user runs:
   * > help <command>
   * @var string
   */
  protected $help = '';

  /**
   * Immutable state object passed-down from the Application context. Set in non-interactive mode using
   * the "-s" param. Use it the same way you'd use "-d" in a commandline Curl request
   * @var \Sheldon\ImmutableObject
   */
  protected $state;


  public function __get($var)
  {
    if (in_array($var, array('pattern', 'command', 'state', 'hidden', 'help', 'cli', 'required', 'optional', 'description', 'arguments')))
    {
      return $this->{$var};
    }

    return null;
  }

  public function __toString()
  {
    return $this->command;
  }

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @param Shell\StreamWriter $write
   * @return mixed
   */
  public abstract function run(Array $matches, StreamWriter $write);

  /**
   * Do any post-match, pre-dispatch validation. Do the right params exist? That sorta thing.
   * @param array $matche
   * @param $input
   * @return bool
   */
  protected function validate($matches, $input)
  {
    return true;
  }

  /**
   * When the command is part of a Context, this is called when the Context initializes itself.
   * Copies immutable state from the Context down to the Command
   * @param array $state
   */
  public function initialize(\Sheldon\ImmutableObject $state)
  {
    $this->state = $state;
  }

  /**
   * Match the supplied $input against the $pattern and return the matches
   * @param $input
   * @return array
   */
  public function match($input)
  {
    $matches = array();
    preg_match($this->pattern . $this->pattern_flags, $input, $matches);
    return $matches;
  }

  /**
   * The only way to run a Command object.
   * If you invoke run() directly the $cli object will not be available.
   * @param $input
   * @param array $matches
   * @param StreamWriter $writer
   */
  public function __invoke($input, Array $matches, StreamWriter $writer)
  {
    // We don't want to use the normalized input because it is lowercased.
    $this->setup_cli(trim($input['raw']));

    if ($this->option('help'))
    {
      $this->usage();
      return;
    }

    if ($this->validate($input['raw'], $matches))
    {
      $this->run($matches, $writer);
      return;
    }

    $this->usage();
  }

}
