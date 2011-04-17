<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id: Trigger.php 576 2009-04-24 10:11:33Z kornel $
 * @link     http://phptal.org/
 */


/**
 * Interface for Triggers (phptal:id)
 *
 * @package PHPTAL
 */
interface PHPTAL_Trigger
{
    const SKIPTAG = 1;
    const PROCEED = 2;

    public function start($id, $tpl);

    public function end($id, $tpl);
}
