# Introduction

_Tequila_ is a basic PHP shell.

It currently has only one feature: instantiating objects and calling methods on
them.

It seems small but is in fact enough to have a very a functioning shell with
various utility classes.

# Syntax

A basic command has the following format:

	class method arg1 ... argN # Comment

Where each of this entries (except the comment) can be:

- a boolean value (`true` or `false`, case insensitive);
- the null value (`null`, case insensitive);
- a string;
- a nested command.

## String formats

There is 3 formats which aim to be enough for any use.

The first and simplest is _plain_: only alphanumerics, **/**, **-**, **_**,
**.** and escaped sequences are allowed, but they are very light to type and
therefore much used for classes and methods names.

	this\ is\ a\ plain\ string

The second one is the _quoted_ format which provides an easy way to type much
strings. They start and end with a quote (**"**), every characters are allowed
but quotes and backslashes must be escaped, you even may use escaped sequences.

	"This is a quoted string"

The last one is _raw_ and allows the user to type raw string very easily without
needing to escape special characters. They start with a percent sign (**%**)
followed by a start delimiter which can be anything but an alphanumeric, a space
or a control character, and they end with the same character except for **(**,
**[**, **{** and **<** where it is the opposite (respectively **)**, **]**,
**}** and **>**). Thus you may use the character which suits the best your
string. Please note that matching pairs inside the string are ignored.

	%{A raw string}
	%(Backslashes do not need to be escaped \, and nested pairs are allowed.)

Escaped sequences:

- \n: New line
- \r: Carriage return
- \t: Tabulation
- \\: Backslash itself
- \": Quote (only for quoted strings)
- \ : Space (only for naked strings)

## Variables

Variables associate a identifier to a value.

An identifier is a string which conforms to the following regular expression
`/[a-z0-9_]+/i`.

A variable starts with a dollar sign (**$**) followed by its identifier.

	class method $my_variable

## Nested commands

Nested commands are mainly used to add dynamicity to scripts but can very handy
in many other situations.

A nested command starts with a dollar sign followed by a left parenthesis
(**$(**) and ends with a right parenthesis (**)**).

	class1 method $(class2 method)

# Architecture

## Run sequence

### Bootstrapping

The `tequila` script is executed, it

1. sets the include path;
2. loads Gallic if necessary and configures it (used for class loading);
3. instantiates a new `Tequila` object;
4. uses the configuration file to… configure Tequila;
5. calls `Tequila::start()`.

### Input loop

`Tequila::start()` runs a loop which reads and executes commands until it is
asked to stop.

1. It ensures it was not already running, otherwise throws an exception.
2. While it is running it:
   1. asks for a command to execute with `Tequila::prompt($prompt)`;
   2. if not empty it is pushed on the history;
   3. calls `Tequila::executeCommand($command)`;
   3. if there was an error, prints it, otherwise prints the return value if not
      `null`.

### Command execution

`Tequila::executeCommand($command)`

1. parses the given command if necessary with `Tequila::parseCommand($command)`;
2. executes all nested commands (with itself);
3. executes the command with `Tequila::execute($class, $method, $args)`.
