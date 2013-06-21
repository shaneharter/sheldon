<?php
namespace Sheldon;
final class Trace extends Command
{

  protected $pattern = '/^trace/';
  protected $description = 'Toggle tracing and logging messages on and off';
  protected $command = 'trace';

  public function __construct()
  {
    $this->hidden = !Application::instance()->is('debug');
  }

  public function run(Array $input, StreamWriter $write)
  {
    $app = Application::instance();
    $app->tracing = !$app->tracing;

    if ($app->tracing)
    {
      $write->styled('Debug Tracing Enabled', '<success>');
    }
    else
    {
      $write->styled('Debug Tracing Disabled', '<success>');
    }

    $write->eol();
  }
}