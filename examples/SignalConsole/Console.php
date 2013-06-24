<?php
namespace Sheldon\examples\SignalConsole;
use Sheldon;

require_once APP_PATH . 'Commands.php';
require_once APP_PATH . 'Contexts.php';

/**
 * The SignalConsole allows you to attach to a PID and then easily send signals to it.
 * @package Sheldon\examples\SignalConsole
 */
class Console extends Sheldon\Application
{

  protected function setup()
  {
    return array(
      new DefaultContext(),
    );
  }

}