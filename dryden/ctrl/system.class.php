<?php

/**
 * System command execution class.
 * @package zpanelx
 * @subpackage dryden -> ctrl
 * @version 1.1.0
 * @author Kevin Andrews (kandrews@zpanelcp.com)
 * @copyright ZPanel Project (http://www.zpanelcp.com/)
 * @link http://www.zpanelcp.com/
 * @license GPL (http://www.gnu.org/licenses/gpl.html)
 */
class ctrl_system
{

    /**
     * Safely run an escaped system() command.
     * @param string $command The command of which to be executed.
     * @param array or string $args Any arguments seperated by a space should be in a seperate array value.
     * @return string
     */
    static function systemCommand($command, $args)
    {
        $escapedCommand = $command;
        if (is_array($args)) {
            foreach ($args as $arg) {
                $escapedCommand .= ' ' . $arg;
            }
        } else {
            $escapedCommand .= ' ' . $args;
        }
        $escapedCommand = escapeshellcmd($escapedCommand);
        echo $escapedCommand;
        system($escapedCommand, $systemReturnValue);
        return $systemReturnValue;
    }
}