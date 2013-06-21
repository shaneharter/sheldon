<?php
namespace Sheldon;

/**
 * An immutable value object.
 */
final class ImmutableObject
{

  private $state = array();

  /**
   * Create the ImmutableObject object. Values passed-in get severed from any existing references
   * @param array $state
   */
  public function __construct(Array $state)
  {
    foreach($state as $key => $val)
    {
      $this->state[$key] = $this->value($val);
    }
  }

  /**
   * Access an item from the $state array by key
   * @param $key
   * @return null
   */
  public function __get($key)
  {
    if (isset($this->state[$key]))
    {
      return $this->value($this->state[$key]);
    }

    return null;
  }

  /**
   * See, this is an immutable object. Nothing to __set().
   */
  public function __set($k, $v)
  {

  }

  /**
   * Return a new ImmutableObject object that extends the current state with more information.
   * @param $more_state   Any keys that collide with the current $state are ignored
   * @return ImmutableObject
   */
  public function extend(Array $more_state)
  {
    return new static($this->state + $more_state);
  }

  /**
   * Export a copy of all state, can be used to merge 2 ImmutableState objects together (calling export() on one,
   * and passing it to the extend() of the other).
   * @return Array
   */
  public function export()
  {
    $out = array();
    foreach($this->state as $key => $val)
    {
      $out[$key] = $this->value($val);
    }
    return $out;
  }

  /**
   * Prevent PHP's Reference semantics from letting a state-change in through the back door.
   * All state items are passed through here when they come into the object constructor and again when they leave through the getter.
   * This accomplishes:
   * 1. Breaks implicit pass-by-reference semantics on Objects
   * 2. Breaks explicit references created on arrays or scalar types
   * @param mixed $var
   * @return mixed
   */
  private function value($var)
  {
    if (is_object($var))
    {
      return clone($var);
    }

    return $var;
  }
}