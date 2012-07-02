- multi-line parsing

- improve and test the “teq” command.

- In case of PHP errors, logs it and displays a short explanation to the
  user. (done?)

- Provides an alias system.

- Case insensitive classes loading.

- Case insensitive for commands?

- Null command.

- Adds a debug mode which prints exceptions stack trace.

- Catches as much as possible, data written by commands (output buffering).

# Dynamic command arguments

In recordings (scripts) there is sometimes the need to have dynamic arguments,
for instance with date manipulations.

## Solution 1: Command preprocessor.

There could be a command preprocessor which is run before the execution and does
only text manipulation.

Downsides:

- difficult to extend (at least at run-time);
- new syntax to develop which should be powerful and do not conflict with the
  existing one.

## Solution 2: Nested commands.

As in the shell, there could be nested queries which are replaced by their
return value.

For the syntax we propose something similar to the one used for raw strings
(“%”, start delimiter, string and end delimiter) but using “$” instead of “%”
for the prefix.

Trivial example supposing the existence of a `teq date` command:

    teq writeln $(teq date)
