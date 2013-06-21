#!/usr/bin/php
<?php
namespace Sheldon\examples\calculator;

define('APP_PATH', __DIR__ . '/');
require APP_PATH . '../src/Application.php';

use Sheldon;
use Sheldon\Context;

class Calculator extends Sheldon\Application
{
  protected $banner = 'Awful Calculator Example';

  /**
   * Process any $this->cli commands, perform any other one-time setup tasks, and return an Array of Context objects
   * to be instianted into the Context linked-list. First element of your array becomes the new list head.
   *
   * @return Context[]
   */
  protected function setup()
  {
    return array(
      new CalculatorContext()
    );
  }
}


class CalculatorContext extends Sheldon\Context
{
  protected function setup()
  {
    return array(
      new Multiplication(),
      new Division(),
      new Addition(),
      new Subtraction(),
      new Factor(),
      new Evaluator,
    );
  }
}

class FactorContext extends Sheldon\Context
{
  protected function setup()
  {
    return array(
      new ComputeFactor(),
      new ExitFactor(),
    );
  }

  public function prompt()
  {
    return sprintf('%d * ? >', $this->state->factor);
  }
}




class Addition extends Sheldon\Command
{
  protected $pattern        = '/^add/';
  protected $command        = 'add';
  protected $description    = 'Add two numbers';
  protected $arguments      = array(
    'l_addend' => 'A number',
    'r_addend' => 'A number to add to the other number'
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @return mixed
   */
  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    $writer($this->argument('l_addend') + $this->argument('r_addend'))->eol();
  }
}

class Subtraction extends Sheldon\Command
{
  protected $pattern        = '/^sub/';
  protected $command        = 'sub';
  protected $description    = 'Subtract <subtrahend> from <minuend>';
  protected $arguments      = array(
    'minuend'     => 'A number to subtract from',
    'subtrahend'  => 'The quantity to subtract'
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @return mixed
   */
  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    $writer($this->argument('minuend') - $this->argument('subtrahend'))->eol();
  }
}

class Multiplication extends Sheldon\Command
{
  protected $pattern        = '/^mul/';
  protected $command        = 'mul';
  protected $description    = 'Multiply <multiplicand> by <multiplier>';
  protected $arguments      = array(
    'multiplicand'  => 'A number to multiply',
    'multiplier'    => 'A factor to multiply by'
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @return mixed
   */
  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    $writer($this->argument('multiplicand') * $this->argument('multiplicand'))->eol();
  }
}

class Division extends Sheldon\Command
{
  protected $pattern        = '/^div/';
  protected $command        = 'div';
  protected $description    = 'Divide <dividend> by <divisor>';
  protected $arguments      = array(
    'dividend'  => 'A number to divide',
    'divisor'   => 'The number to divide by'
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @return mixed
   */
  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    $writer($this->argument('dividend') / $this->argument('divisor'))->eol();
  }
}

class Evaluator extends Sheldon\Command
{
  protected $pattern        = '/^(\d+)\s*([+-\/\*])\s*(\d+)/';
  protected $command        = 'Expression';
  protected $description    = 'Compute simple arithmetic expressions';
  protected $arguments      = array(
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @return mixed
   */
  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    list($_, $left_operand, $operator, $right_operand) = $matches;
    $evaluator = $this->evaluator($operator);
    $writer($evaluator($left_operand, $right_operand))->eol();
  }

  /**
   * Return the proper evaluator closure.
   * Obviously we could just pass the whole matched string to eval()
   * But what would be the fun in that...
   * @return Callable
   */
  private function evaluator($operator)
  {
    switch($operator)
    {
      case '+': return function($a, $b) { return $a + $b; };
      case '-': return function($a, $b) { return $a - $b; };
      case '*': return function($a, $b) { return $a * $b; };
      case '/': return function($a, $b) { return $a / $b; };
    }

    throw new Sheldon\CommandException('Unknown Operator! Given: ' . $operator);
  }
}

class Factor extends Sheldon\Command
{
  protected $pattern        = '/^factor/';
  protected $command        = 'factor';
  protected $description    = 'Easily compute factors of the given multiplicand';
  protected $arguments      = array(
    'multiplicand'  => 'The number we will be factoring',
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @return mixed
   */
  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    $multiplicand = $this->argument('multiplicand');
    if ($multiplicand === null)
    {
      $multiplicand = Sheldon\Number::prompt();
    }

    if (!is_numeric($multiplicand))
    {
      throw new Sheldon\CommandException('Numeric multiplcand required. Given: ' . $multiplicand);
    }

    $ctx = new FactorContext(array(
      'factor' => $multiplicand
    ));

    throw new Sheldon\ContextException($ctx);
  }
}


class ComputeFactor extends Sheldon\Command
{
  protected $pattern        = '/^[-+]?([0-9]*\.[0-9]+|[0-9]+)$/';
  protected $command        = 'Number';
  protected $description    = 'Enter the number to multiply against the number used in the `factor` command.';

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @return mixed
   */
  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    $writer($this->state->factor * $matches[0])->eol();
  }
}

/**
 * Exit the FactorContext back to the default CalculatorContext
 * This is an example of the ability to overload commands. In this case, the FactorContext is overloading the
 * "exit" command that already exists in the Sheldon DefaultContext.
 *
 * The way we accomplish this looks tricky but it's not so bad. In most applications you won't just want to exit
 * a Context the way we are here. In most cases you might want to REPLACE a context. You can see that happening
 * by looking at the Factor command. It lives in the Calculator context and it inserts a new FactorContext object.
 * If we are already inside a FactorContext object, the existing object gets replaced by the new.
 *
 * In this case though, we just want to escape this context. Our Context list (remember: Contexts are a linked-list)
 * looks like this:
 *
 * (Calculator Application context_head) -> FactorContext -> CalculatorContext -> (DefaultContext)
 *
 * So we are passing a callable to the each() method. We know the first Context that will be passed is FactorContext.
 * We skip that, and then whatever the next context is, we pass to set_context_head. This removes the refernece to
 * FactorContext and the object will then be garbage collected. We then $break() the iteration.
 *
 * @package Sheldon\examples\calculator
 */
class ExitFactor extends Sheldon\Command
{
  protected $pattern        = '/^exit$/';
  protected $command        = 'exit';
  protected $description    = 'Exit the Factor prompt';

  public function run(Array $matches, Sheldon\StreamWriter $writer)
  {
    $app = Calculator::instance();
    $app->each(function($context, $_, $break) use($app) {
      if ($context instanceof FactorContext) return;
      $app->set_context_head($context);
      $break();
    });
  }
}


Calculator::instance()->start();