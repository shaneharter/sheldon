<?php

namespace Sheldon;
use Sheldon\ImmutableObject;

/**
 * Class Context
 * Represents a group of Commands that should be grouped together because they share the same state or some other
 * logical reason.
 *
 * Contains an immutable $state object that holds:
 * 1) State added when the Context::initialze() method was called (usually as it's instantiated)
 * 2) A copy of the state from all previous Context objects in the linked-list.
 *
 * Preferred Usage:
 * Extend the Context class and implement the setup() method to return an array of Command objects. You can also
 * optionally implement the prompt() method if you would like to use logic in this context to print a prompt.
 *
 * Alternateve Usage:
 * Instantiate and compose a Context object, passing N Command objects to the add() method, and optionally setting a
 * value for the $prompt variable.
 *
 * A Command object can add a new Context to the linked-list by throwing a ContextException.
 *
 * @package Sheldon
 */
class Context
{

  /**
   * If you extend Context and implement a prompt() method, that will be called. If no prompt() method exists,
   * this value will be checked. If this remains null, the Application will try to get a prompt from the $next Context.
   * @var null
   */
  public $prompt = null;

  /**
   * Immutable state object.
   * Contains state created when this context object was instantiated as well as all state details from previous contexts
   * on the stack.
   * @var \Sheldon\ImmutableObject
   */
  protected $state;

  /**
   * The next context in the linked list
   * @var Context
   */
  protected $next;

  /**
   * @var Command[]
   */
  protected $commands = array (

  );



  public function __construct(Array $state = array())
  {
    $this->state = new ImmutableObject($state);
  }

  public function __destruct()
  {
    unset($this->next, $this->commands, $this->state);
  }

  public function __get($var)
  {
    if (in_array($var, array('state', 'prompt', 'commands')))
    {
      return $this->{$var};
    }

    return null;
  }


  /**
   * If you are creating your own Context objects (the suggested way of doing things), you can overload
   * this to dynamically build a prompt, for example by including details from the context $state.
   *
   * Alternatively, the $prompt variable will be used.
   *
   * If no prompt is set in this context, it will walk down to the $next context and look for a prompt there. If no
   * prompt is set in your context objects, the application will use the Application::$default_prompt;
   * @return string
   */
  public function prompt()
  {
    // Optionally Implement
    return null;
  }

  /**
   * Will be called each iteration of the event loop. Return false and this Context will be distroyed.
   * If it IS destroyed, the $next Context is set as the new list head.
   * @example If you were building an application to first select a process and then send various signals to it,
   *          you might want the SelectedProcess context to automatically exit if the process it's signaling exits.
   *
   * @return bool
   */
  public function criteria()
  {
    // Optionally Implement
    return true;
  }

  /**
   * If you are creating your own Context objects (the suggested way of doing things), you can overload
   * this to return an array of Command objects. The alternate way of doing things is to instantiate a Context
   * object and call its add() method, passing-in the Command objects for that Context.
   * @return Command[]
   */
  protected function setup()
  {
    // Optionally Implement
    return array();
  }

  /**
   * Iterate the Commands objects in this Context and apply the given Callable to each command.
   *
   * Aggregation:
   * The return value of your Callable is passed, after the next Command object, as the 2nd parameter of the next call.
   * You can build up a string, append to an array, sum an integer, whatever. You can also carry
   * forward the Nth value or the min or the max, etc. You can supply a seed value for the first call of your
   * Callable by passing it as the 2nd parameter when you call Context::each().
   *
   * Breaking the Loop:
   * A function will be passed as the 3rd parameter to your Callable. Calling it will disable iteration and the value
   * returned by that Callable will be returned to the initial each() caller.
   *
   * @param Callable $callable  Any valid Callable.
   * @param mixed $aggregate    Optional aggregate seed data/struct passed as 2nd parameter to your callable.
   * @return mixed
   */
  public function each($callable, $aggregate = null)
  {
    // Here $breaker closes-over the $break boolean that controls the foreach() loop iteration.
    // It is passed as the 3rd param to the $callable.
    // If a closure needs to break out of iteration, it can invoke that 3rd parameter.
    $break    = false;
    $breaker  = function() use(&$break) {
      $break  = true;
    };

    foreach($this->commands as $command)
    {
      $aggregate = $callable($command, $aggregate, $breaker);
      if ($break) break;
    }

    return $aggregate;
  }

  /**
   * Add Command objects to the stack.
   * Accepts variable number of commands.
   * @return void
   */
  public function add(Command $command1, Command $commandN = null)
  {
    foreach (func_get_args() as $command)
    {
      if (! $command instanceof Command)
      {
        throw new \Exception('Instance of "Command" Expected. Given: ' . get_class($command));
      }

      $this->commands[] = $command;
    }
  }


  /**
   * Linked-List related function.
   *
   * Will return the next Context object in the linked list, or null.
   * @return null|Context
   */
  public function next()
  {
    if ($this->next)
    {
      return $this->next;
    }

    return null;
  }

  /**
   * Linked-List related function.
   *
   * Does this Context link to another or is this the end of the linked list?
   * @return bool
   */
  public function has_next()
  {
    return ($this->next() !== null);
  }

  /**
   * Linked-List related function.
   *
   * Called by whatever is instantiating this context. This will either be:
   * 1. The Application::setup() method you overload in your Application subclass. All initial contexts must be added there.
   * 2. Another Context object that catches a ContextException thrown by a Command.
   *    When that happens, the calling Context object passes its own $this reference as the new $tail. This new object
   *    becomes the head and the first element in the linked list.
   *
   * @param Context $new_node
   * @param Array $state        Inject the supplied state into the Context $state. Only usable when Application is not running in interactive mode.
   */
  public function append(Context $tail, Array $state = array())
  {
    $this->next = $tail;

    // Each context contains its own state info, passed-in when the object was created. That info is merged with an aggregate
    // of the state of all previous Context objects in the list. The state object is immutable. Part of being immutable,
    // any key collisions are dropped, keeping the original k/v. In other words, state keys in THIS $head context will
    // be ignored if it conflicts with state set in a previous $tail context.
    $this->state = $tail->state->extend($this->state->export());

    // Only when run in non-interactive mode, you can inject state into the Context objects.
    if (!empty($state) && !Application::instance()->is('interactive'))
    {
      $this->state = new ImmutableObject($state + $this->state->export());
    }

    // If there are no commands (e.g. the add() method hasn't been called yet) assume its because this is a subclassed
    // Context object that has a setup() method that will return an array of Command objects. Feed it into add().
    if (empty($this->commands))
    {
      if ($commands = $this->setup())
      {
        call_user_func_array(array($this, 'add'), $commands);
      }
    }

    // The commands in this context are given a copy of the immutable state so they can do their jobs.
    foreach($this->commands as $command)
    {
      $command->initialize($this->state);
    }

    // The Application singleton always keeps a reference to the head $context on the list.
    Application::instance()->set_context_head($this);
  }

}