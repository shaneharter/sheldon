<p align="center">
<img height="250" width="188" src="http://upload.wikimedia.org/wikipedia/en/thumb/2/2d/Sheldon_Cooper.jpg/250px-Sheldon_Cooper.jpg">
</p>

##Sheldon
###Create Awesome Commandline Applications in PHP

Applications built with Sheldon are object oriented, maintain state in a sensible way, provide integrated help documentation, can be ran as an interactive shell or as a script, display in color when the terminal supports it, and leverage the fantastic Console features in the latest versions of Zend Framework and Symfony.

Use Sheldon to build awesome console applications in no time at all.

#### Requires: ####
* PHP 5.3 or Higher
* PHP PCNTL Extension

#### Notable Features:

* ### Object Oriented Framework
Your singleton Application class extends `Sheldon\Application`. Individual commands are implemented in classes that extend `Sheldon\Command`. Command objects are organized into Contexts. A linked-list of Context objects is created and your Application object always references the list head. User input is passed down the Context list, compared to each Command's RegEx pattern until a match is found.

* ### State Sanity
When running in interactive shell mode, your application may need to maintain state for the duration. The core concepts of state in Sheldon are simple:
 1. State is immutable.
 1. State is attached to a Context object. New state? Create a new Context object. Changed state? Swap one Context object for another.
 1. Contexts carry-forward the state from all previous contexts. (Remember: Contexts are a linked list). When the Context is created, any new state is merged with the existing  state and then sealed to any changes.
 1. A Context's state is copied down to all the Command objects attached to it.

 By enforcing immutable state, it eliminates freshness and synchronization concerns.

* ### Integrated Help Documentation
Each Command class defines its own regex pattern, required params (with however many short (-x) and long (--xxx) aliases you'd like), optional params, and positional arguments. This configuration data is used both operationally, and to display a standardized Usage block and help documentation.

* ### An Interactive Shell or a Script
In Interactive Shell mode, your application will start-up, display a command prompt, and wait for user input. Users run commands, and commands sometimes create or augment state (by adding or changing the current Context).

 In Script mode, a user specifies a Command to run and any applicable parameters and arguments. When the Application starts it routes the input to the correct Command object, runs the Command, then exits.

* ### In Living Color

 While you can still use the plain-old `echo` statement, an instance of the `StreamWriter` class is passed to each `Command` object. The `StreamWriter` combines features from ZendFramwork and the Symfony2 Console component to give you:
 1. **Colored Text**. As simple as passing a string and a color code, you can apply foreground and background colors in terminals with Xterm256 support. When support is not available, the text is displayed cleanly in monochrome, free of unparsed Xterm markup.
 1. **Style Tags**. Integration of the Symfony2 `OutputFormatter` enables you to include XML-like style tags in your text. Several built-in styles like `<error></error>` and `<success></success>` exist and can be extended by your application. Style tags can define foreground and background colors and text formatting like "bold" or "italic". Terminals without support for styled output have their tags stripped beforehand for a user experience that degrades gracefully.
 1. **Columnar and Tabular Formatting**. The `StreamWriter::column()` and `StreamWriter::table()` methods will display cleanly aligned and easily skimmable data. Columnar layouts are simple and clean using string padding on each row to produce columns of text. Tablular layouts are fantastic and work exactly how you want, with column headers, borders, gutters, and the ability to integrate Style Tags throughout.
 1. **Drawing**. Your application can draw simple lines and boxes to the terminal with ease.