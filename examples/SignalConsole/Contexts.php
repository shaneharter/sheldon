<?php
namespace Sheldon\examples\SignalConsole;
use Sheldon\Application;
use Sheldon\Context;

/**
 * Class DefaultContext
 * Loaded when the Console starts up
 * @package Sheldon\examples\SignalConsole
 */
class DefaultContext extends Context
{
  public function setup()
  {
    return array(
      new Pid(),
    );
  }
}

/**
 * Class PidContext
 * The PidContext is loaded after a user specifies a PID
 * @package Sheldon\examples\SignalConsole
 */
class PidContext extends Context
{
  public function setup()
  {
    return array(
      new Signal(),
    );
  }

  /**
   * A loaded PidContext is only valid as long as the attached PID is running
   * @return bool
   */
  public function criteria()
  {
    if (!Pid::valid($this->state->pid))
    {
      Application::instance()->write->styled('Attached Process No Longer Running...', '<warning>');
      return false;
    }

    return true;
  }

  public function prompt()
  {
    return sprintf('[PID %s] SIG > ', $this->state->pid);
  }
}