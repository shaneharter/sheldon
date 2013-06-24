<?php
namespace Sheldon;
declare(ticks = 5);

define('SRC_PATH', __DIR__ . '/');
define('VENDORS_PATH', dirname(SRC_PATH) . '/vendors/');

use \Zend\Console\Getopt;

require_once SRC_PATH . 'Cli.php';
require_once SRC_PATH . 'Command.php';
require_once SRC_PATH . 'commands/ExitApplication.php';
require_once SRC_PATH . 'commands/Help.php';
require_once SRC_PATH . 'commands/Trace.php';
require_once SRC_PATH . 'commands/State.php';
require_once SRC_PATH . 'commands/Evaluator.php';
require_once SRC_PATH . 'commands/CatchAll.php';
require_once SRC_PATH . 'symfony/OutputInterface.php';
require_once SRC_PATH . 'symfony/shim.php';
require_once SRC_PATH . 'Context.php';
require_once SRC_PATH . 'ContextException.php';
require_once SRC_PATH . 'CommandException.php';
require_once SRC_PATH . 'ImmutableObject.php';

// @todo Replace this with an autoloader wrapper for the \Symfony namespace
require_once VENDORS_PATH . 'Symfony/Console/Helper/HelperInterface.php';
require_once VENDORS_PATH . 'Symfony/Console/Helper/Helper.php';
require_once VENDORS_PATH . 'Symfony/Console/Helper/HelperSet.php';
require_once VENDORS_PATH . 'Symfony/Console/Helper/TableHelper.php';
require_once VENDORS_PATH . 'Symfony/Console/Formatter/OutputFormatterInterface.php';
require_once VENDORS_PATH . 'Symfony/Console/Formatter/OutputFormatterStyleInterface.php';
require_once VENDORS_PATH . 'Symfony/Console/Formatter/OutputFormatter.php';
require_once VENDORS_PATH . 'Symfony/Console/Formatter/OutputFormatterStyle.php';
require_once VENDORS_PATH . 'Symfony/Console/Formatter/OutputFormatterStyleStack.php';
require_once SRC_PATH     . 'symfony/PosixStyle.php';

// @todo Replace this with an autoloader wrapper for the \Zend namespace
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Getopt.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Console.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Adapter/AdapterInterface.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Adapter/AbstractAdapter.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Adapter/Posix.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/ColorInterface.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Prompt/PromptInterface.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Prompt/AbstractPrompt.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Prompt/Char.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Prompt/Confirm.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Prompt/Line.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Prompt/Number.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Prompt/Select.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Exception/ExceptionInterface.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Exception/InvalidArgumentException.php';
require_once VENDORS_PATH . 'Zend/library/Zend/Console/Exception/RuntimeException.php';

require_once SRC_PATH . 'prompts/Char.php';
require_once SRC_PATH . 'prompts/Confirm.php';
require_once SRC_PATH . 'prompts/Line.php';
require_once SRC_PATH . 'prompts/Number.php';
require_once SRC_PATH . 'prompts/Question.php';
require_once SRC_PATH . 'prompts/Select.php';
require_once SRC_PATH . 'Colors.php';
require_once SRC_PATH . 'StreamWriter.php';

/**
 * A CLI Shell Application
 *
 * 1. Extend this class, overloading required methods and properties, optionally overloading others
 *
 * 2. Your setup() method should return an array of initial Context objects. At least one with the initial commands
 *    of your app. Read the PHPDoc on the Context class for details.
 *
 * 3. Your Context objects should be composed of Command objects. Read the PHPDoc on the Command class for details.
 *
 * General Architecture & Guidance:
 *
 * 0. Contexts are a linked list. There is a list sentinel that holds builtin commands. A reference to the list head
 *    is always set at Application::instance()->context_head.
 *
 * 1. All state is stored at the Context level and, once created, is immutable.
 *
 * 2. Command objects have no idea what Context they are in (nor should they) but they do have a copy of the Context's
 *    immutable state.
 *
 * 3. The immutable state of a Context object is copied-forward all the way to the head Context in the list.
 *
 * 4. The prompt is printed by walking down the list and using the first prompt encountered.
 *
 * Applying this with an example application:
 *
 * 1. Imagine an accounting application, and focus specifically on finding a user, viewing his invoices, and
 *    recording payments to the account.
 *
 * 2. The Application will have 3 Context classes, though only the first will be instantiated when the application
 *    loads: AppContext, UserContext, InvoiceContext.
 *
 * 3. The AppContext includes a FindUser Command object, among many others we'll ignore for now. When a user opens the
 *    app, and types `FindUser 100` the FindUser command will lookup user "123" and load the users details from the
 *    database. It needs to push this information into a new UserContext object. To do so, throw a ContextException
 *    that conatins the new Context object and whatever other state is desired. This will be caught by the AppContext
 *    object. The AppContext object will set itself as the tail of this new UserContext object, and the UserContext
 *    object becomes the new Application::$context_head.
 *
 * 4. Now, if you type "show invoices" it will first check the UserContext object. It will find a match and display a
 *    list of invoices to the screen. No context or state change would happen.
 *
 * 5. Next, you want to load user 101. You enter the command `FindUser 101`. As always, command dispatch starts with
 *    the list head, which is the UserContext object for user_id=100. There is no FindUser command in the UserContext
 *    so it falls-through to the AppContext. It will repeat Step #3: It will query for user_id=101, instantiate a new
 *    UserContext with user_id=101's details in the local $state. As before, AppContext will call UserContext::append()
 *    and pass itself as the $tail argument. We now have a list that starts with this new UserContext object that links
 *    to the AppContext object that created it. Application::context_head is set to the new list head.
 *
 *    In other words: the old list head (the UserContext object for user_id=100) was removed and replaced by a new
 *    UserContext object.
 *
 * 6. Now suppose you use the FindInvoice command to load invoice_id=200. The InvoiceContext is created, it becomes the
 *    head, and it links to the UserContext. It responds to commands like `MarkAsPaid` or `ApplyPayment $100`.
 *
 * 7, At that point if you were to repeat your first command, `FindUser 100`, it would create a new UserContext, load
 *    it, link to the AppContext, and update the context_head. Your list fragment of InvoiceContext(200) linking-to
 *    UserContext(101) is garbage collected as it is no longer linked to the Context list in any way.
 *
 * Hopefully this example illustrates how state is managed and applications are composed. The Application instance
 * remains eternally stateless and immutable state provides for easyily understandable flow of data.
 *
 * @package Sheldon
 * @singleton
 *
 * @todo Enable auto-loader, probably need to wrap the ZF2 autloader with ours.
 */
abstract class Application extends Cli
{
  /**
   * Application Exit Codes. To avoid magic numbers.
   */
  const EXIT_CLEAN      = 0;
  const EXIT_ERROR      = 1;
  const EXIT_SIGNAL     = 2;
  const EXIT_EXCEPTION  = 3;

  /**
   * Application Setting Keys. To avoid magic conincidental naming.
   */
  const DEBUG           = 'debug';
  const TRACING         = 'tracing';
  const INTERACTIVE     = 'interactive';
  const SHUTDOWN        = 'shutdown';
  const STATE           = 'state';


  /**
   * Singleton Instance
   * @var Application
   */
  protected static $instance = null;


  /**
   * If no $prompt variable or prompt() method is implemented in the context stack, this default
   * prompt will be used.
   *
   * @var string
   */
  protected $default_prompt = '>';

  /**
   * The banner is printed when the application starts.
   * @var string
   */
  protected $banner = '';

  /**
   * @var StreamWriter
   */
  protected $write;

  /**
   * A record of all input received. Newest entries at the bottom.
   * Records are an assoc array with keys: raw, normal containing the raw  & processed user input
   * @var Array[]
   */
  protected $input = array( /* array('raw' => 'RaW InpuT  ', 'normal' => 'raw input') */ );

  /**
   * A reference to the head of the Context linked-list. The list implements recursive traversal instead of iterative
   * because we only need to traverse it when calling dispatch() and prompt(). It does not need to serve as a general
   * purpose data structure. Generally recursive traversal is easier anyway.
   * @var Context
   */
  protected $context_head;

  /**
   * When enabled, various debug tracing messages will be printed (think: command dispatching)
   * @var bool
   */
  protected $settings = array(
    self::DEBUG         => false,   // Debug mode makes commands like "trace" visible in the help list but so far that's it
    self::TRACING       => false,   // When Tracing is enabled, the dispatcher prints command and context information
    self::INTERACTIVE   => true,    // Intractive = Shell Mode
    self::SHUTDOWN      => false,   // Shutdown = Will exit at the end of the current event loop iteration
  );


  /**
   * Get a singleton instance of your Application object
   * @return Application
   */
  public static function instance($getopt = null, $stream_writer = null)
  {
    if (static::$instance === null)
    {
      static::$instance = new static();
      static::$instance->cli      = $getopt         ?: null;  // Instantiation of default handled in the base class.
      static::$instance->write    = $stream_writer  ?: new StreamWriter();
      static::$instance->setup_application();
    }

    return static::$instance;
  }

  /**
   * Process any $this->cli commands, perform any other one-time setup tasks, and return an Array of Context objects
   * to be instianted into the Context linked-list. First element of your array becomes the new list head.
   *
   * Note:
   * If you want your application to support a non-interactive commandline driven mode, you should know that only the
   * Contexts and Commands that are returned by this setup() method will be available from the commandline. A good
   * pattern is to check the $this->interactive flag. If it's false, you may want to load Contexts differently.
   *
   * In commandline mode, context state is passed in using multiple -d parameters. For example:
   *
   * $ ./myapp SomeStatefulCommand \
   *    -d user_id=1001 \
   *    -d "user[fname]=Shane" \
   *    -d "user[lname]=Harter"
   *
   * In that example, the Command and Context objects will be passed a state array with "user_id" and "user" keys.
   *
   * @return Context[]
   */
  protected abstract function setup();

  /**
   * Optionally implement. You are passed the array of "raw" and "normal" input and should return an array with
   * the same format.
   * @param array $input
   * @return array
   */
  protected function process_input(Array $input)
  {
    return $input;
  }


  private function __construct() {}

  public function __destruct()
  {
    unset($this->context_head, $this->getopt, $this->write);
    echo PHP_EOL;
  }

  public function __get($var)
  {

    if (in_array($var, array('cli', 'default_prompt', 'banner', 'input', 'write')))
    {
      return $this->{$var};
    }

    if (isset($this->settings[$var]))
    {
      return $this->get($var);
    }

    return null;
  }

  public function __set($var, $val)
  {
    $this->set($var, $val);
  }

  /**
   * Get a value from the Application::$options registry.
   * Registry includes any data set using the Application::set() method, plus any options that were passed on the
   * commandline and defined in Application::$optional or Application::$required.
   * @param $key
   */
  public function get($key)
  {
    return @ ($this->settings[$key]) ?: null;
  }

  /**
   * Alias of get() for more self-documenting code.
   * @example if ($app->is('interactive'))
   * @param $key
   * @return null
   */
  public function is($key)
  {
    return $this->get($key);
  }

  /**
   * Set a value in the Application::$options registry.
   * @param $key
   * @param $val
   */
  public function set($key, $val)
  {
    $this->settings[$key] = $val;
  }

  /**
   * Start the application loop. Handle input. Etc.
   * @return void
   */
  public function start()
  {
    // Build the Context list.
    // Append the list-sentinal DefaultContext object to the end. Handles built-in commands and provides a
    // guarantee that Application::context_head will always point to SOMETHING. 
    $this->setup_contexts();

    // If a command was passed, disable shell mode, dispatch the command, and exit.
    if (!$this->is('interactive'))
    {
      if ($command = $this->argument("command"))
      {
        global $argv;
        $input = array(
          'raw'     => implode(' ', array_slice($argv, 1)),
          'normal'  => implode(' ', array_slice($argv, 1))
        );
        $this->dispatch($input);
      }

      exit(self::EXIT_CLEAN);
    }

    // Write the Welcome Banner
    $this->write
      ->eol()
      ->styled($this->description, '<notice>')->eol()
      ->styled('See `help` for more information.', '<optional>')->eol()
      ->styled('Ctrl-C to Exit, Ctrl-D to Escape', '<optional>')->eol()
      ->eol();

    // Start the Application Loop.
    $prompt = true;
    while(!$this->shutdown)
    {
      // We run filter_contexts every iteration to ensure any runtime criteria for a context is satisfied.
      // If a context is filtered, we always want to re-write the prompt in case the Context filter changed it.
      if ($this->filter_contexts())
      {
        $this->write->eol();
        $prompt = true;
      }

      if ($prompt)
      {
        $this->prompt();
      }

      usleep(50000);
      $input  = $this->read_input();
      $prompt = !is_null($input);

      if ($input && $input['normal'] != '')
      {
        $this->dispatch($input);
        $this->write->eol();
      }
    }


  }

  /**
   * Sets the starting-point to the $context linked-list.
   * That is, Application singleton points to first element on the $context list, which points to the next, etc
   * @param Context $context
   */
  public function set_context_head(Context $context)
  {
    unset($this->context_head);
    $this->context_head = $context;
  }

  /**
   * Exit Application. If a clean exit code is being used, it will let the current iteration of the event loop complete.
   * Otherwise it will exit immediately.
   * @param $code   The exit code. Can be any integer, but would be great to stick to the enumerated EXIT_* constants.
   * @todo Possibly add an event dispatch for this sort of thing, or just go simple and add an $on_shutdown Callable[] queue
   */
  public function quit($code = self::EXIT_CLEAN, $friendly = true)
  {
    if ($friendly)
    {
      $this->shutdown = true;
      return;
    }

    exit($code);
  }

  /**
   * Helper method to implement basic error-message handling.
   * Optionally overload in your app to implement a error logging strategy.
   * @param $message  The message to print to the screen
   * @param $fatal    When true, the application will exit
   */
  public function error($message, $fatal = false)
  {
    $this->write->styled("<error>Error: $message</error>")->eol();

    if ($fatal)
    {
      $this->quit(self::EXIT_ERROR, false);
    }
  }

  /**
   * Iterate the Context list and apply the given Callable to each node.
   *
   * Aggregation:
   * The return value of your Callable is passed, after the next Context object as the 2nd parameter of the next call.
   * You can build up a string, append to an array, sum an integer, whatever. You can also carry
   * forward the Nth value or the min or the max, etc. You can supply a seed value for the first call of your
   * Callable by passing it as the 2nd parameter when you call Application::each().
   *
   * Breaking the Loop:
   * A function will be passed as the 3rd parameter to your Callable. Calling it will disable iteration and the value
   * returned by that Callable will be returned to the initial each() caller.
   *
   * @param Callable $callable  Any valid Callable.
   * @param mixed $aggregate    Optional aggregate seed data/struct passed as 2nd parameter to your callable.
   * @param Context $head       Optional list starting point. If ommitted uses Application::context_head
   * @return mixed
   */
  public function each($callable, $aggregate = null, Context $head = null)
  {
    // The Application class will always add a default Context object to handle built-ins like "help" and "exit"
    // That object also is our list sentinel. We can trust that $context_head is always referencing a Context object.
    // That said, if a $head is provided, use it. Otherwise use the $context_head.
    if (is_null($head))
    {
      $head = $this->context_head;
    }

    // Here $breaker closes-over the $break boolean that controls the while() loop iteration.
    // If a closure needs to break out of iteration, it can invoke that 3rd parameter.
    // A $break context variable wouldn't work in a nested iterator (and would require users to explicitely add a & for references)
    // And the raw $break param couldn't be passed because it too would require the & in the closure definition.
    // Passing the $break() closure seems the best and most reliable option.
    $break    = false;
    $breaker  = function() use(&$break) {
      $break  = true;
    };

    do
    {
      if (!is_null($head))
      {
        $aggregate = $callable($head, $aggregate, $breaker);
      }
    }
    while(!$break && $head = $head->next());

    return $aggregate;
  }

  /**
   * Dispatch the $input to each Context until it is matched with a Command
   * Ok, this is f'n confusing to look at, because it uses a nested iterator:
   * A closure is writtern that accepts a $context and passes an inner closure to the $context::each() method.
   * And that outer closure is itself passed to the Application::each() method.
   * @param $input  The input array, with 'raw' and 'normal' keys
   */
  protected function dispatch(Array $input)
  {
    $app = $this;

    // The $walker Closure will iterate over all the Context objects. Within each context it will iterate
    // over all Commands with the $matcher closure.
    $walker = function(Context $context, $_, $break) use($input, $app) {

      try
      {
        // The $matcher Closure will iterate over all commands in the Context. If a match is found, it will
        // invoke the command and $break() the iterator.
        $matcher = function(Command $command, $_, $break) use($input, $app, $context) {
          if ($matches = $command->match($input['normal']))
          {
            if ($app->tracing)
            {
              $app->write->styled(sprintf('Dispatch: %s::%s', get_class($context), get_class($command)))->eol();
            }

            $command($input, $matches, $app->write);
            $break();
            return true;
          }

          return false;
        };

        if (!$context->each($matcher))
        {
          return;
        }
      }
      catch(ContextException $e)
      {
        if ($app->tracing)
        {
          $this->write->styled(sprintf('New Context Frame %s from %s', get_class($e->context), get_called_class($this)))->eol();
        }

        $head = $e->context;
        $head->append($context);
        unset($e);
      }
      catch(\Exception $e)
      {
        Application::instance()->error($e->getMessage());
        return;
      }


      // If we're here either 1) There was a match returned from the $matcher() function, or 2) An exception thrown.
      // In either case, we want to break iteration of the each-context loop.
      $break();
    };

    $this->each($walker);
  }

  /**
   * Plain old signal handler.
   * When responding to Ctrl+C exit signal, try first to let the event loop exit naturally. If that doesn't
   * happen within a reasonable timeout, and the user re-sends the signal, just exit immediately.
   * @param $signal
   */
  public function signal($signal)
  {
    static $signals = array();
    static $timeout = 3;  // seconds

    switch ($signal)
    {
      case SIGKILL:
        // Just kidding. PHP exits on SIGKILL so there's nothing we can do in our script to catch it.
        // Just wanted to document that fact.
        break;

      case SIGINT:
      case SIGTERM:

        // When we get a Ctrl-C (or `kill [pid]`) signal, set a timeout.
        // If the event loop is being blocked it won't exit right away. If an additional signal is sent after the
        // $timeout period, interrupt the event loop and exit.

        if (isset($signals[$signal]))
        {
          $friendly_exit = (microtime(true) - $signals[$signal]) < $timeout;
        }
        else
        {
          $friendly_exit = true;
          $signals[$signal] = microtime(true);
        }

        $this->write->styled('<notice>Exit Signal Captured...</notice>')->eol();
        $this->quit(self::EXIT_SIGNAL, $friendly_exit);
        break;
    }
  }

  /**
   * Gets the prompt from the Context list.
   * If none is returned, uses the default prompt
   * @return string
   */
  private function prompt()
  {
    $prompt = $this->each(function(Context $context, $aggregate) {

      // If a prompt() method is added to a $context, use it. Otherwise read the $prompt value. Return the first of
      // those we come across.
      switch(true) {
        case (!is_null($aggregate)):            return $aggregate;
        case (!is_null($context->prompt())):    return $context->prompt();
        default:                                return $context->prompt;
      }
    });

    if (!$prompt)
    {
      $prompt = $this->default_prompt;
    }

    $this->write->styled(StreamWriter::rspace($prompt));
  }

  /**
   * Convert the initial Context stack returned by $this->setup() into the Context List and set the
   * Application::context_head reference.
   * @return void
   */
  private function setup_contexts()
  {
    if ($this->context_head)
    {
      return;
    }

    $contexts = $this->setup();
    $default_context = new Context();
    $default_context->add(new ExitApplication(), new Help(), new Trace(), new State(), new Evaluator(), new CatchAll());
    $contexts[] = $default_context;

    /**
     * @var Context $context
     */
    krsort($contexts);
    $state = $this->get(self::STATE) ?: array();
    foreach($contexts as $context)
    {
      if ($tail = $this->context_head)
      {
        $context->append($tail, $state);
      }

      $this->context_head = $context;

    }
    unset($tail, $context, $contexts);
  }

  /**
   * One-time setup to attach signal handling and CLI options.
   * @return void
   */
  private function setup_application()
  {
    stream_set_blocking(STDIN, 0);
    pcntl_signal(SIGINT,  array($this, 'signal'));
    pcntl_signal(SIGTERM, array($this, 'signal'));

    // Define and Parse the CLI Options
    // These defaults are added to any $optional and $required options defined in the extending class
    // The "numeric flag sentinel" argument is here because if we omit it, and this is used in non-interactive mode,
    // and the user passes in a numeric flag (eg -100), the Getopt parser will throw an exception. s
    $this->optional += array (
      'state|s=s' => 'Add state data. This option is ignored during interactive shell mode.',
      'debug|d'   => 'Run application in Debug mode.',
      'num|n-#'   => 'Numeric Flag Sentinel: Numeric flags will be passed to the the given command.',
      'help|h'    => 'Display Application Help. For command list, use "help" not "--help"',
    );

    $this->arguments += array (
      'command'   => 'Run given command and quit. Non-interactive shell mode. Arguments are passed-through to the command.'
    );

    $this->instructions += array(
      'Command Listing' => 'Pass the "help" command (not the "--help" option) to see a list of available commands' . PHP_EOL,
      'Setting State' => 'You can easily verify that the state arguments being passed-in are parsed the way you desire by ' . PHP_EOL .
                         'running the running the "state" command from outside the shell. For example:' . PHP_EOL .
                         '$ yourapp.php state -s "key1=val1" -s "key2[x]=1" -s "key2[y]=2"' . PHP_EOL . PHP_EOL.
                         'Furthermore, you can use the same "state" command when running in interactive mode to display ' . PHP_EOL .
                         'the current state of a Context object. This will make it easy to know what "-s" state params you' . PHP_EOL .
                         'will need to pass to a command when running it directly from the commandline'
    );

    $this->setup_cli();

    // Now handle any provided options & args
    $this->interactive = ($this->argument('command') === null);

    // If there's a --help param passed on the commandline, display the Command help if a Command was specified.
    // Otherwise display Application help and exit:
    if ($this->option('help') && $this->interactive) {
      $this->usage();
      exit();
    }

    if ($this->option("state"))
    {
      // We need to parse the state params into the desired data structure.
      // Supports arbitrary nesting, e.g. this does what you'd expect:
      // $ foo -s "[items][0][id]=1001" -s "[items][1][id]=1002"
      // If only one -s param is passed, it won't automatically be an array, so first standardize that and then parse.
      $state = $this->option("state");
      if (!is_array($state)) $state = array($state);

      $array = array();
      foreach($state as $statum)
      {
        parse_str($statum, $statum);
        $array = array_merge_recursive($array, $statum);
      }

      $this->set(self::STATE, $array);
    }
  }

  /**
   * Give each Context a chance to bail out if a criteria() method was implemented.
   * This can easily wipe out multiple Contexts in one go. Imagine:
   * [List Head] -> ContextA -> ContextB -> ContextC -> DefaultContext
   *
   * If ContextB::criteria() returns false, the list will become:
   * [List Head] -> ContextC -> DefaultContext
   *
   * @return bool Returns True if a Context was filtered
   */
  private function filter_contexts()
  {
    $that = $this;
    return $this->each(function(Context $context, $exited) use($that) {
      if (!$context->criteria())
      {
        $that->set_context_head($context->next());
        return true;
      }

      return $exited;
    }, false);

  }

  /**
   * Read input from STDIN, normalize, log both raw and normal versions, and return normalize input
   * @return string
   */
  private function read_input()
  {
    // We arae polling, not blocking, so most the time there will be no input.
    if (($stdin = fgets(STDIN)) === false)
    {
      return null;
    }

    // Build an input struct with the raw and normalized input
    $input = array(
      'raw'     => $stdin,
      'normal'  => strtolower(trim($stdin))
    );

    switch(true)
    {
      // If the input was submitted with an EOF (Ctrl-D) instead of EOL, ignore the input
      case substr($input['raw'], -1) != PHP_EOL:
        $this->write->eol();
        return array('raw' => false, 'normal' => '');
        break;

      // Replay the most previous input on !!
      case $input['normal'] == '!!':
        $input = $this->input[count($this->input)-1];
        break;
    }

    $input = $this->process_input($input);

    if (!empty($input['normal']))
    {
      $this->input[] = $input;
    }
    return $input;
  }
}