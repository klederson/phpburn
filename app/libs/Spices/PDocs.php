<?php
namespace PhpBURN\Spices\PDocs;

/**
 * <p>
 *  PDocs is a simple and fast phpBURN Spice that allow you to reand and manipule
 *  methods, class and functions documentation in real time by reading your files
 *  and extracting:
 * 
 *  * Documentation
 *  * Code Attributes
 *  * Tags such as @author, @version and others
 * </p>
 *
 * @author Klederson Bueno <klederson@phpburn.com>
 * @version 1.0
 * @see www.phpburn.com/features/spices/PDocs
 * @package Spices
 * @subpackage PDocs
 */
class PDocs {
  
  const OBJECT = 'object';
  const METHOD = 'method';
  const ATTR = 'attr';
  
  private $obj;
  private $cacheComment;
  
  public function __construct($object) {
    $this->obj = &$object;
  }
  
  public function listMethods() {
    
  }
  
  /**
   * <p>
   *  Polymorphic method that allows you to retreive both Object, Attributes and Methods documentation
   *  from the current object.
   * </p>
   * 
   * @param String $method 
   */
  public function getDocumentation($target = NULL, $type = self::OBJECT) {
    if($target == NULL && $type != self::OBJECT)
      return FALSE;
    
    switch($type) {
      case self::OBJECT:
        $target = $target == NULL ? $this->obj : $target;
        $reflection = new ReflectionClass($target);
        break;
      case self::METHOD:
        $reflection = new ReflectionMethod($this->obj,$target);
        break;
      case self::ATTR:
        $reflection = new ReflectionProperty($this->obj, $target);
        break;
      default:
        return FALSE;
        break;
    }
    
    if(is_object($target))
      $target = get_class($target);
    
    return $this->cacheComment[@get_class($this->obj)][$target] = $reflection->getDocComment();
  }
  
  
  /**
   * <p>
   *  Polymorphic method that allows you to retreive both Object, Attributes and Methods Comment Tag
   *  documentation from the current object.
   * 
   *  You should pass tagName to specify the tag you want to and if you want to get
   *  a method tag you must also specify the method name.
   * </p>
   * 
   * @param String $tag
   * @param String $target
   * @param String $type
   */
  public function getCommentTag($tag, $target = NULL, $type = self::OBJECT) {
    $docComment = empty($this->cacheComment[@get_class($this->obj)][$target]) ? $this->getDocumentation($target,$type) : $this->cacheComment[get_class($this->obj)][$target];
    
    $matches = array();
    preg_match("/" . $tag . " (.*)(\\r\\n|\\r|\\n)/U", $docComment, $matches);

    if (isset($matches[1])) {   
      return trim($matches[1]);
    }

    return FALSE;
  }
  
  
}

?>
