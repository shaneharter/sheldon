<?php
namespace Sheldon;
final class ExitApplication extends Command
{

  protected $pattern = '/^exit$/';
  protected $description = 'Quit this Application';
  protected $command = 'exit';

  /**
   * Do the actual thing. The regex-parsed input is passed.
   * @param array $input
   * @return mixed
   */
  public function run(Array $input, StreamWriter $write)
  {
    Application::instance()->quit();
  }
}