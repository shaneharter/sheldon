<?php
/**
 * We want to use some Symfony2 components -- The Table Helper and Formatter to begin with. They have only one external
 * dependency, on the Output_Interface, used to write to stdout. We can't use the Symfony2 Output_Interface without
 * adopting several other pieces of the Symfony stack. So to get around this, we're creating an imposter
 * Output_Interface, and implementing that in a simple shim class that passes the calls to our StreamWriter.
 */

namespace Sheldon;

class Shim implements \Symfony\Component\Console\Output\OutputInterface
{
  private $writer;
  private $verbosity = self::VERBOSITY_NORMAL;

  public function __construct(StreamWriter $writer)
  {
    $this->writer = $writer;
  }

  public function writeln($message)
  {
    $this->writer->styled($message)->eol();
  }

  public function write($message, $newline = false)
  {
    if ($newline)
    {
      $this->writer->styled($message)->eol();
    }
    else
    {
      $this->writer->styled($message);
    }
  }

  public function getVerbosity()
  {
    return $this->verbosity;
  }

  public function setVerbosity($level)
  {
    $this->verbosity = $level;
  }
}