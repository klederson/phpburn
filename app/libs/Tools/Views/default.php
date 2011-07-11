<?php

PhpBURN::load('Tools.Views.IProcessView');

class default_PhpBURN_ViewProcess implements IProcessView {

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
    //Starting a new buffer
    ob_start();

    //Parsing Array data to Variables
    foreach ($__phpBurnData as $__index => $__value) {
      if (is_string($value))
        $value = PhpBURN_Views::lazyTranslate($value);
      
      $$__index = $__value;
    }

    include($___phpBurnFilePath);

    //Storing buffer result to a var
    $___phpBurnBufferStored = ob_get_contents();

    //Cleaning the buffer for new sessions
    ob_clean();

    return $___phpBurnBufferStored;
  }

}

?>
