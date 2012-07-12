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
 *   cmd     = [ whitespaces ] entry whitespaces entry { whitespaces entry } [ whitespaces ]
 *   entry   = boolean | null | naked_str | quoted_str | raw_str | variable | subcmd
 *   comment = '#' *anything*
 *
 *   whitespaces  = regex(\s+)
 *   boolean      = regex(/true|false/i)
 *   null         = regex(/null/i)
 *   naked_str    = *escaped sequence*
 *   quoted_str   = '"' *escaped sequence* '"'
 *   raw_str      = '%' start_delim characters end_delim
 *   variable     = regex(/\$[a-z0-9_]+/i)
 *   subcmd       = '$(' cmd ')'
 *
 * @author Julien Fontanet <julien.fontanet@isonoe.net>
 *
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

	private function _assert($string)
	{
		$this->_check($string)
			or $this->_fail('expected “'.$string.'”');
	}

	private function _check($string)
	{
		$length = strlen($string);

		if (($this->_i < $this->_n)
		    && (substr_compare($this->_s, $string, $this->_i, $length) === 0))
		{
			$this->_i += $length;
			return true;
		}

		return false;
	}

	private function _regex($re, &$match = null)
	{
		if (!preg_match($re.'A', $this->_s, $match, 0, $this->_i))
		{
			return false;
		}

		$this->_i += strlen($match[0]);
		return true;
	}

	private function _fail($reason = '')
	{
		throw new Tequila_IncorrectSyntax($this->_i, $reason);
	}

	//--------------------------------------

	private function _cmdline()
	{
		$cmd = $this->_cmd();

		$this->_comment();

		// Everything was not parsed.
		($this->_i === $this->_n) or $this->_fail('unexpected token');

		return $cmd;
	}

	private function _cmd()
	{
		$this->_whitespaces();

		if (!$this->_entry($class))
		{
			throw new Tequila_UnspecifiedClass($this->_i);
		}

		$this->_whitespaces() or $this->_fail('missing whitespace');

		if (!$this->_entry($method))
		{
			throw new Tequila_UnspecifiedMethod($class, $this->_i);
		}

		$args = array();
		while ($this->_whitespaces() && $this->_entry($e))
		{
			$args[] = $e;
		}

		return new Tequila_Parser_Command($class, $method, $args);
	}

	/**
	 * Note: see _boolean().
	 */
	private function _entry(&$val)
	{
		return (
			$this->_null($val)
			|| $this->_boolean($val)
			|| $this->_nakedStr($val)
			|| $this->_quotedStr($val)
			|| $this->_rawStr($val)
			|| $this->_variable($val)
			|| $this->_subcmd($val)
		);
	}

	private function _comment()
	{
		return $this->_regex('/#.*/');
	}

	private function _whitespaces()
	{
		return $this->_regex('/\\s+/');
	}

	/**
	 * Note: Due to implementation it is not possible to return the “false”
	 * value. Therefore the return value indicates whether the rule matches and
	 * the value is stored in the “$val” argument.
	 */
	private function _boolean(&$val)
	{
		if (!$this->_regex('/(?:true|false)(?=\s+|$)/i', $match))
		{
			return false;
		}

		$val = (strcasecmp($match[0], 'true') === 0);
		return true;
	}

	private function _null(&$val)
	{
		if (!$this->_regex('/null(?=\s+|$)/i'))
		{
			return false;
		}

		$val = null;
		return true;
	}

	private function _nakedStr(&$val)
	{
		if ($this->_regex('#(?:[a-zA-Z0-9-_./]+|(?:\\\\.))+#', $match))
		{
			$val = $this->_parseString($match[0], ' ');
			return true;
		}

		return false;
	}

	private function _quotedStr(&$val)
	{
		if ($this->_regex('/"((?:[^"\\\\]+|(?:\\\\.))*)"/', $match))
		{
			$val = $this->_parseString($match[1], '"');
			return true;
		}

		return false;
	}

	private function _rawStr(&$val)
	{
		if (!$this->_regex('/%([^[:alnum:][:cntrl:][:space:]])/', $match))
		{
			return false;
		}
		$sd = $match[1];
		$ed = self::_getOpposite($sd);

		// Quoted versions of start and end delimiters.
		$qed = preg_quote($ed, '/');
		$qsd = preg_quote($sd, '/');

		$this->_regex('/(?:[^'.$qsd.$qed.']+|'.$qsd.'(?R)'.$qed.')*/', $match);
		$val = $match[0];

		$this->_assert($ed);

		return true;
	}

	private function _variable(&$val)
	{
		if (!$this->_regex('/\$([a-z0-9_]+)/i', $match))
		{
			return false;
		}

		$val = new Tequila_Parser_Variable($match[1]);
		return true;
	}

	private function _subcmd(&$val)
	{
		if (!$this->_check('$('))
		{
			return false;
		}

		$val = $this->_cmd();

		$this->_assert(')');

		return true;
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
