<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * This module provides data providers iteration to feed repeatable commands
 *
 * @author marsaud
 */
final class iterate extends Tequila_Module
{

    /**
     * Iterates a file to feed a command or script with it's line's content.
     *
     * There are 2 ways to exploit the output of the iteration :
     *
     * 1) Directly in the command by using the special parameter "hook", that will be replaced by the iterated line's
     *    content (hook can be used several times along the command parameters).
     *
     *    Example : iterate file myFile teq writeln hook
     *
     * 2) In a command recalled by a "record play" usage, by evaluating the $iterate variable (set to the iterated
     *    line's content);
     *
     *    Example :
     *      iterate file myFile record play myScript.teq
     *    With myScript.teq as following :
     *      teq writeln $iterate
     *
     * CAUTION : hook and $iterate must be considered as tequila reserved keyword and variable from the moment you use
     * the iterate module.
     *
     * @param string $filePath a relative path to an input file.
     * @param string $className A tequila command class
     * @param string $methodName A tequila command method (followed by it's parameters)
     *
     * @throws Exception If file does not exist
     *
     * @return void
     */
    public function file($filePath, $className, $methodName)
    {
        $args = func_get_args();
        $nestedArgs = array_slice($args, 3);

        $hooks = array();

        /**
         * We reference the hooks parameters for replacement
         */
        foreach ($nestedArgs as &$value)
        {
            $matches = array();
            if (preg_match('/^hook([0-9]*)$/', $value, $matches))
            {
                $hookNumber = isset($matches[1]) ? (integer) $matches[1] : 0;
                isset($hooks[$hookNumber])
                    || $hooks[$hookNumber] = array();

                $hooks[$hookNumber][] = &$value;
                $value = NULL;
            }
        }

        $handle = fopen($filePath, 'r');
        if (false === $handle)
        {
            throw new Exception('File could not be opened: ' . $filePath);
        }

        while (false !== ($line = fgetcsv($handle, 0, ';', '"')))
        {
            foreach ($this->_tequila->variables as $varName => $varValue)
            {
                if (preg_match('/^iterate[0-9]*$/', $varName))
                {
                    unset($this->_tequila->variables[$varName]);
                }
            }

            $this->_tequila->setVariable('iterate', $line[0]);
            foreach ($line as $key => $cellValue)
            {
                $this->_tequila->setVariable('iterate' . $key, $cellValue);
            }

            $missingHooks = false;
            foreach ($hooks as $hookNumber => $hookReferences)
            {
                if (!array_key_exists($hookNumber, $line))
                {
                    $line[$hookNumber] = NULL;
                    $missingHooks = true;
                }

                foreach ($hookReferences as &$reference)
                {
                    $reference = $line[$hookNumber];
                }
            }

            $skip = false;
            if ($missingHooks)
            {
                $promptLegalValues = array('', NULL, 'yes', 'no');
                $prompt = $this->_tequila->promptSecure(
                    'Execute iteration with missing hooked parameters ? (NO/yes) :', $promptLegalValues, true
                );

                switch ($prompt)
                {
                    case '':
                    case NULL :
                    case 'no':
                        $skip = true;
                        break;
                    case 'yes':
                        $skip = false;
                        break;
                    default:
                        // not reached
                        $skip = true;
                        break;
                }
            }

            if ($skip)
            {
                continue;
            }

            try
            {

                $result = $this->_tequila->execute($className, $methodName, $nestedArgs);
            }
            catch (Exception $exc)
            {
                $this->_tequila->writer->writeln(gettype($exc) . ': ' . $exc->getMessage(), true);
                $result = NULL;
            }

            if (NULL !== $result)
            {
                $this->_tequila->writeln($this->_tequila->prettyFormat($result));
            }
        }

        fclose($handle);

        $this->_tequila->writeln();
        $this->_tequila->writeln('End of iteration on file ' . $filePath);
    }

}
