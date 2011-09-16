<?php

PhpBURN::load('Tools.Views.IProcessView');
PhpBURN::load('Addons.PHPTAL.PHPTAL');
PhpBURN::load('Addons.PHPTAL.PHPTAL.GetTextTranslator');

class dcampos_PhpBURN_ViewProcess implements IProcessView {

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
    
    $tr = new PHPTAL_GetTextTranslator();
    // set language to use for this session (first valid language will
    // be used)
    $tr->setLanguage('pt_BR.utf8', 'pt_BR');

    // register gettext domain to use
    $tr->addDomain('system', SYS_BASE_PATH . 'locale');

    // specify current domain
    $tr->useDomain('system');

    // tell PHPTAL to use our translator
    $tpl->setTranslator($tr);

    foreach ($__phpBurnData as $index => $value) {
      if (is_string($value))
        $value = PhpBURN_Views::lazyTranslate($value, $_SESSION['lang']);

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
