<?php
/**
 * Start this process to test the SignalConsole
 */

namespace Sheldon\examples\SignalConsole;
declare(ticks=5);

echo "\nSignal Tester\n";
echo "Started with PID: " . getmypid() . PHP_EOL;

$signals = array (
  SIGTERM, SIGINT, SIGUSR1, SIGHUP, SIGCHLD,
  SIGUSR2, SIGCONT, SIGQUIT, SIGILL, SIGTRAP, SIGABRT, SIGIOT, SIGBUS, SIGFPE, SIGSEGV, SIGPIPE, SIGALRM,
  SIGCONT, SIGTSTP, SIGTTIN, SIGTTOU, SIGURG, SIGXCPU, SIGXFSZ, SIGVTALRM, SIGPROF,
  SIGWINCH, SIGIO, SIGSYS, SIGBABY
);

if (defined('SIGPOLL'))     $signals[] = SIGPOLL;
if (defined('SIGPWR'))      $signals[] = SIGPWR;
if (defined('SIGSTKFLT'))   $signals[] = SIGSTKFLT;

foreach($signals as $signal)
{
  pcntl_signal($signal, function($signal) {
    echo PHP_EOL, "Signal Rec'd: " . $signal;

    if ($signal == SIGTERM || $signal == SIGINT)
    {
      echo PHP_EOL, "Exiting...", PHP_EOL, PHP_EOL;
      exit();
    }
  });
}

while (true)
{
}