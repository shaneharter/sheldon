<?php
namespace Sheldon\examples\SignalConsole;
use Sheldon;

//require_once SHELDON_PATH . 'Application.php';

/**
 * The SignalConsole allows you to attach to a PID and then easily send signals to it.
 * @package Sheldon\examples\SignalConsole
 */
class Console extends Sheldon\Application
{

  protected $default_prompt = 'PID >';


  protected function setup()
  {
    // TODO: Implement setup() method.
  }


}