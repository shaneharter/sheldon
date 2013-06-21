<?php



namespace Sheldon;
use Zend\Console\Getopt;

final class Help extends Command
{
  protected $pattern = '/^help\s*/';
  protected $description = 'Command listing and usage help.';
  protected $command = 'help';

  protected $optional = array(
    'verbose|v' => 'Specify terse or verbose output!',
  );

  protected $arguments = array(
    'command'   => 'Display usage block for given Command',
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $input
   * @return void
   */
  public function run(Array $input, StreamWriter $writer)
  {
    switch(true)
    {
      case $command = $this->argument('command'):   $this->command($command, $writer);  break;
      case $this->option('verbose'):                $this->verbose($input, $writer);    break;
      default:                                      $this->listing($input, $writer);    break;
    }

    return;
  }

  /**
   * Print just the commands, not descriptions
   * @return void
   */
  public function listing($input, $writer)
  {
    // Walk the Context list and get all commands in a nested array
    $commands_by_context = Application::instance()->each (
      function(Context $context, $aggregate) {
        $aggregate[] = $context->commands;
        return $aggregate;
      },
      array()
    );

    // All of a context's commands will be printed inline, sorted alphabetically.
    // Each context gets its own line. Contexts are reverse sorted, eg the commands on $context_head will be printed last.
    krsort($commands_by_context);

    foreach($commands_by_context as $commands)
    {
      if (empty($commands))
      {
        continue;
      }

      $writer->eol();
      usort($commands, function($a, $b) {
        return strcmp($a->command, $b->command);
      });

      foreach($commands as $command)
      {
        if ($command->hidden)
        {
          continue;
        }

        $writer->styled($this->stylize($command->command) . "     ");
      }
    }

    $writer->eol();
  }

  /**
   * Print commands with descriptions
   * @return void
   */
  public function verbose($input, $writer)
  {
    // Walk the Context list and get all commands in a single array
    $commands = Application::instance()->each (
      function(Context $context, $aggregate) {
        return array_merge($context->commands, $aggregate);
      },
      array()
    );

    $data = array();
    krsort($commands);
    foreach($commands as $command) {
      if ($command->hidden) continue;
      $data[] = array($command->command, $command->description);
    }

    $writer->column($data);
  }

  /**
   * Iterate through contexts almost the way Application::dispatch() does to match the given command $input to
   * a Command object. When we find one, print its usage block.
   * @param $input
   * @param $writer
   */
  public function command($input, $writer)
  {
    Application::instance()->each (
      function(Context $context, $_, $break) use($input, $writer) {
        $found = $context->each (
          function(Command $command, $_, $break) use($input) {
            if ($command->match($input))
            {
              $command->usage();
              $break();
              return true;
            }
          }
        );

        if ($found) $break();
      }
    );
  }

  public function stylize($text)
  {
    // If the text is already styled, leave it be. Assume i'ts styled if we have
    if (StreamWriter::is_styled($text))
    {
      return $text;
    }

    $parts = array();
    if (preg_match('/(\S+)\s(.+)/', $text, $parts) == 0)
    {
      return $text;
    }

    $head = $parts[1];
    $tail = $parts[2];

    return sprintf('%s <optional>%s</optional>', $head, $tail);
  }

}