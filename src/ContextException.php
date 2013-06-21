<?php
namespace Sheldon;

/**
 * Context Exceptions are thrown by Command objects when they need to push or swap a new context object onto the stack.
 * It will be caught by whatever context called the Command::run() method, which is and should be unknown to the Command.
 *
 * The catching context will initialize the supplied $context object and make it the new list head.
 * @package Sheldon
 */
class ContextException extends \Exception
{
  public $context;

  public function __construct(Context $context = null)
  {
    $this->context = $context;
    parent::__construct();
  }
}