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
         * We reference the hook parameter for replacement
         */
        foreach ($nestedArgs as &$value)
        {
            if ('hook' == $value)
            {
                $hooks[] = &$value;
            }
        }

        $handle = fopen($filePath, 'r');
        if (false === $handle)
        {
            throw new Exception('File could not be opened: ' . $filePath);
        }

        while (false !== ($line = fgets($handle)))
        {
            $line = trim($line, "\n");

            $skip = false;
            if ('' == $line)
            {
                $promptLegalValues = array('', NULL, 'yes', 'no');
                do
                {
                    $prompt = $this->_tequila->prompt('Execute iteration with empty line ? (NO/yes) :');
                    $prompt = trim(strtolower($prompt));
                }
                while (!in_array($prompt, $promptLegalValues));

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

            $this->_tequila->setVariable('iterate', $line);
            foreach ($hooks as &$hook)
            {
                $hook = $line;
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
