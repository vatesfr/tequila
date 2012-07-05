<?php

/**
 * This parser splits a string into multiple entries.
 *
 * Entries can be either NULL or strings.
 *
 * There is two types of strings:
 * - interpolated  strings where  some sequences  have a  special  meanings (see
 *   “Escape sequences” list);
 * - raw strings.
 *
 * Interpolated strings can be:
 * - quoted with “"”;
 * - naked (non quoted).
 *
 * Escape sequences:
 * - \n: New line
 * - \r: Carriage return
 * - \t: Tabulation
 * - \\: Backslash itself
 * - \": Quote (only for quoted strings)
 * - \ : Space (only for naked strings)
 *
 * A raw string begins with a “%” followed by a delimiter character which can be
 * anything excepts alphanumeric, whitespace  and control characters. The string
 * ends with  the same  character except for  the opening  “(”, “[”, “{”  or “<”
 * where  it ends with  the closing  character (respectively  “)”, “]”,  “}” and
 * “>”). Please, note that matching pairs inside the string are ignored.
 *
 * Grammar:
 *
 *   cmdline = cmd [ comment ] regex(/$/)
 *   cmd     = [ whitespaces ] entry [ whitespaces ] entry [ whitespaces ] { entry [ whitespaces ] }
 *   entry   = boolean | null | naked_str | quoted_str | raw_str | subcmd
 *   comment = '#' *anything*
 *
 *   whitespaces  = regex(\s+)
 *   boolean      = regex(/true|false/i)
 *   null         = regex(/null/i)
 *   naked_str    = *escaped sequence*
 *   quoted_str   = '"' *escaped sequence* '"'
 *   raw_str      = '%' start_delim characters end_delim
 *   subcmd       = '$' start_delim cmd end_delim
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
 * @todo Use return value to indicates if the rule matches and the &$val
 *     parameter to return the value.
 * @todo Handle Unicode alphanumerics in naked strings.
 */
final class Tequila_Parser
{
	/**
	 * Tries to parse a given string.
	 *
	 * @param string $s
	 *
	 * @return Tequila_Parser_Command
	 *
	 * @throws Tequila_IncorrectSyntax   If the syntax was incorrect and
	 *     therefore could not be parsed.
	 * @throws Tequila_UnspecifiedClass  If there is a missing class for
	 *     a command.
	 * @throws Tequila_UnspecifiedMethod If there is a missing method for
	 *     a command.
	 */
	public function parse($s)
	{
		$this->_s = $s;

		$this->_i = 0;
		$this->_n = strlen($this->_s);

		return $this->_cmdline();
	}

	//--------------------------------------

	private function _regex($re)
	{
		if (!preg_match($re.'A', $this->_s, $match, 0, $this->_i))
		{
			return false;
		}

		$this->_i += strlen($match[0]);
		return $match;
	}

	//--------------------------------------

	private function _cmdline()
	{
		$cmd = $this->_cmd();

		$this->_comment();

		// Everything was not parsed.
		if ($this->_i < $this->_n)
		{
			throw new Tequila_IncorrectSyntax($this->_i);
		}

		return $cmd;
	}

	private function _cmd()
	{
		$this->_whitespaces();

		if (!$this->_entry($class))
		{
			throw new Tequila_UnspecifiedClass($this->_i);
		}

		$this->_whitespaces();

		if (!$this->_entry($method))
		{
			throw new Tequila_UnspecifiedMethod($class, $this->_i);
		}

		$this->_whitespaces();

		$args = array();
		while ($this->_entry($e))
		{
			$args[] = $e;

			$this->_whitespaces();
		}

		return new Tequila_Parser_Command($class, $method, $args);
	}

	/**
	 * Note: see _boolean().
	 */
	private function _entry(&$val)
	{
		if (($e = $this->_null()) !== false
			or $this->_boolean($e)
			or ($e = $this->_nakedStr()) !== false
			or ($e = $this->_quotedStr()) !== false
			or ($e = $this->_rawStr()) !== false
		    or $e = $this->_subcmd())
		{
			$val = $e;
			return true;
		}

		return false;
	}

	private function _comment()
	{
		return (boolean) $this->_regex('/#.*/');
	}

	private function _whitespaces()
	{
		return (boolean) $this->_regex('/\\s+/');
	}

	/**
	 * Note: Due to implementation it is not possible to return the “false”
	 * value. Therefore the return value indicates whether the rule matches and
	 * the value is stored in the “$val” argument.
	 */
	private function _boolean(&$val)
	{
		if (!($match = $this->_regex('/true|false/i')))
		{
			return false;
		}

		$val = (strcasecmp($match[0], 'true') === 0);
		return true;
	}

	private function _null()
	{
		if ($this->_regex('/null/i'))
		{
			return null;
		}

		return false;
	}

	private function _nakedStr()
	{
		if ($match = $this->_regex('/(?:[a-zA-Z0-9-_.]+|(?:\\\\.))+/'))
		{
			return $this->_parseString($match[0], ' ');
		}

		return false;
	}

	private function _quotedStr()
	{
		if ($match = $this->_regex('/"((?:[^"\\\\]+|(?:\\\\.))*)"/'))
		{
			return $this->_parseString($match[1], '"');
		}

		return false;
	}

	private function _rawStr()
	{
		// Save current position.
		$cursor = $this->_i;

		if (!($match = $this->_regex('/%([^[:alnum:][:cntrl:][:space:]])/')))
		{
			return false;
		}
		$sd = $match[1];
		$ed = preg_quote(self::_getOpposite($sd), '/');
		$sd = preg_quote($sd, '/');

		if ($match = $this->_regex('/((?:[^'.$sd.$ed.']+|'.$sd.'(?1)'.$ed.')*)'.$ed.'/'))
		{
			return $match[1];
		}

		// No match, restore position.
		$this->_i = $cursor;
		return false;
	}

	private function _subcmd()
	{
		// Save current position.
		$cursor = $this->_i;

		if (!($match = $this->_regex('/\$([^[:alnum:][:cntrl:][:space:]])/')))
		{
			return false;
		}
		$sd = $match[1];
		$ed = preg_quote(self::_getOpposite($sd), '/');

		$cmd = $this->_cmd();

		if ($this->_regex("/$ed/"))
		{
			return $cmd;
		}

		// No match, restore position.
		$this->_i = $cursor;
		return false;
	}

	//--------------------------------------

	private function _parseString($str, $delimiter = null)
	{
		$codes = array(
			'n' => "\n",
			'r' => "\r",
			't' => "\t",
			'\\' => '\\',
			'"' => '"',
		);

		if ($delimiter)
		{
			$codes[$delimiter] = $delimiter;
		}

		$str = str_split($str);
		$result = array();
		$escaped = false;
		foreach ($str as $c)
		{
			if ($escaped)
			{
				$result[] = (isset($codes[$c]) ? $codes[$c] : '\\'.$c);
				$escaped = false;
			}
			elseif ($c === '\\')
			{
				$escaped = true;
			}
			else
			{
				$result[] = $c;
			}
		}

		return implode($result);
	}

	/**
	 * @param string $delimiter
	 *
	 * @return string
	 */
	private static function _getOpposite($delimiter)
	{
		static $pairs = array(
			'(' => ')',
			'[' => ']',
			'{' => '}',
			'<' => '>',
		);

		return
			isset($pairs[$delimiter])
			? $pairs[$delimiter]
			: $delimiter
			;
	}
}
