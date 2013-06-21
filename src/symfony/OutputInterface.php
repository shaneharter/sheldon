<?php
/**
 * We want to use some Symfony2 components -- The Table Helper and Formatter to begin with. They have only one external
 * dependency, on the Output_Interface, used to write to stdout. We can't use the Symfony2 OutputInterface without
 * adopting several other pieces of the Symfony stack. So to get around this, we're creating an imposter
 * OutputInterface, and implementing that in a simple shim class that passes the calls to our StreamWriter.
 */

namespace Symfony\Component\Console\Output;

interface OutputInterface
{
  const VERBOSITY_QUIET        = 0;
  const VERBOSITY_NORMAL       = 1;
  const VERBOSITY_VERBOSE      = 2;
  const VERBOSITY_VERY_VERBOSE = 3;
  const VERBOSITY_DEBUG        = 4;

  const OUTPUT_NORMAL = 0;
  const OUTPUT_RAW    = 1;
  const OUTPUT_PLAIN  = 2;
}