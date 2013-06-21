<?php
namespace Sheldon;
final class CatchAll extends Command
{

  protected $pattern = '/.+/';
  protected $hidden = true;

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $input
   * @return mixed
   */
  public function run(Array $input, StreamWriter $write)
  {
    $write->styled('Unknown Command or Option!', '<error>')->eol();
  }
}