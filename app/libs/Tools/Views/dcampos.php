<?php

PhpBURN::load('Tools.Views.IProcessView');
PhpBURN::load('Addons.PHPTAL.PHPTAL');

class phptal_PhpBURN_ViewProcess implements IProcessView {

  /**
   * This method is used to process the data into the view and than return it to the main method that will handle what to do.
   * It also uses buffer to handle that content.
   *
   * @author Klederson Bueno <klederson@klederson.com>
   * @version 0.1a
   *
   * @param String $___phpBurnFilePath
   * @param Array $__phpBurnData
   * @return String
   */
  public function processViewData($___phpBurnFilePath, $__phpBurnData) {
    $tpl = new PHPTAL($___phpBurnFilePath);

    $tpl->setOutputMode(PHPTAL::HTML5);

    foreach ($__phpBurnData as $index => $value) {
      if (is_string($value))
        $value = PhpBURN_Views::lazyTranslate($value);

      $tpl->$index = $value;
    }

    ob_start();

    try {
      echo $tpl->execute();
    } catch (Exception $e) {
      echo $e;
    }

    $___phpBurnBufferStored = ob_get_contents();
//
//        //Cleaning the buffer for new sessions
    ob_clean();

    return $___phpBurnBufferStored;
  }

}

?>
