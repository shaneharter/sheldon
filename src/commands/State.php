<?php
namespace Sheldon;
final class State extends Command
{

  protected $pattern = '/^state\s*/';
  protected $description = 'Dump state for given context frame, 0 as head context, 0+N as subsequent.';
  protected $command = 'state';
  protected $required = array(
    'context|c=#' => 'The state will be printed from this context frame. Pass "0" for the head.'
  );

  public function run(Array $input, StreamWriter $write)
  {
    $selected_context = $this->cli->getOption('context');
    Application::instance()->each( function($context, $distance) use ($selected_context, $write)
    {
      if ($distance == $selected_context)
      {
        $write('Context Object: ' . get_class($context))->eol();

        $state = $context->state->export();
        if (empty($state))
        {
          $write('No state data attached to this context');
        }
        else
        {
          $write(print_r($state, true));
        }
      }

      return ++$distance;
    }, 0);

    $write->eol();
  }
}