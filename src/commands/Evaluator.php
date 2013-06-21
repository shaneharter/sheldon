<?php
namespace Sheldon;
final class Evaluator extends Command
{

  protected $pattern = '/^eval (.*)/';
  protected $description = 'Eval the supplied code. Passed to eval() as-is. Any return values will be printed.';
  protected $command = 'eval';

  protected $arguments = array(
    'code'  => 'The string of code that will be passed to eval(). Objects $write and $application are available.'
  );

  public function __construct()
  {
    $this->hidden = !Application::instance()->is('debug');
  }

  public function run(Array $input, StreamWriter $write)
  {
    $application = Application::instance();

    $return = @eval($this->argument('code'));
    if ($return === false)
      $write("eval returned false -- possibly a parse error. Check semi-colons, parens, braces, etc.", '<warning>');
    elseif ($return !== null)
      $write('Eval Results:', '<success>')->eol()->styled(print_r($return, true));

    $write->eol();
  }
}
