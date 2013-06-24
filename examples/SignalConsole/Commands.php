<?php
namespace Sheldon\examples\SignalConsole;

use Sheldon\Command;
use Sheldon\ContextException;
use Sheldon\StreamWriter;

/**
 * Class Pid
 * Loaded from the Default context, captures numeric input and attaches that Pid.
 * @package Sheldon\examples\SignalConsole
 */
class Pid extends Command
{
  protected $pattern = '/^pid/';
  protected $description = 'Enter the Pid of a running process to attach';
  protected $command = 'pid';
  protected $arguments = array(
    'pid' => 'The process ID to attach',
  );

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @param Shell\StreamWriter $write
   * @return mixed
   */
  public function run(Array $matches, StreamWriter $write)
  {
    if (!$this->valid($this->argument('pid'))) {
      $write('Invalid Pid! Process not running!', '<error>')->eol();
      return;
    }

    $state = array(
      'pid' => $this->argument('pid'),
    );

    $write('Attaching Pid...')->eol();
    throw new ContextException(new PidContext($state));
  }

  public static function valid($pid)
  {
    return file_exists("/proc") && file_exists("/proc/" . $pid);
  }
}


/**
 * Class Signal
 * Loaded from the PID context, captures numeric input and sends that signal to the attached Pid.
 * @package Sheldon\examples\SignalConsole
 */
class Signal extends Command
{
  protected $pattern = '/^\d+/';
  protected $description = 'Enter the signal to send to the attached process';
  protected $command = '<SIGNAL>';

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $matches  Input $matches from the regex.
   * @param Shell\StreamWriter $write
   * @return mixed
   */
  public function run(Array $matches, StreamWriter $write)
  {
    if (!$this->valid($matches[0])) {
      $write('Invalid Signal!', '<error>')->eol();
      return;
    }

    $write('Signal Sent!', '<success>')->eol();
    posix_kill($this->state->pid, $matches[0]);
  }

  private function valid($signal)
  {
    $signals = array (
      SIGTERM, SIGINT, SIGUSR1, SIGHUP, SIGCHLD, SIGKILL,
      SIGUSR2, SIGCONT, SIGQUIT, SIGILL, SIGTRAP, SIGABRT, SIGIOT, SIGBUS, SIGFPE, SIGSEGV, SIGPIPE, SIGALRM,
      SIGCONT, SIGTSTP, SIGTTIN, SIGTTOU, SIGURG, SIGXCPU, SIGXFSZ, SIGVTALRM, SIGPROF,
      SIGWINCH, SIGIO, SIGSYS, SIGBABY
    );

    if (defined('SIGPOLL'))     $signals[] = SIGPOLL;
    if (defined('SIGPWR'))      $signals[] = SIGPWR;
    if (defined('SIGSTKFLT'))   $signals[] = SIGSTKFLT;

    return in_array($signal, $signals);
  }
}