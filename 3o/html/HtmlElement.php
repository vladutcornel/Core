<?php

require_once TRIO_DIR.'/whereis.php';

/**
 * A generic HTML Element
 * @author Cornel Borina <cornel@scoalaweb.com>
 * @package 3oLibrary
 * @subpackage HTML
 * @todo Subclasses should define lists of accepted sub-elements
 */
class HtmlElement extends Element{

    /**
     * @var Style The element's style
     */
    private $style;

    /**
     * @var array Element's classes (class atribute)
     */
    private $classes = array();

    /**
     * @param string $tagName
     * @param string $elementId
     */
    public function __construct($tag = '', $id = ''){
        parent::__construct($tag,$id);

        $singleTags = array('base','br','hr','img','input','param','source','track','wbr');
        if(in_array($tag, $singleTags)){
            $this->setSingletag(true);
        }else{
            $this->setSingletag(false);
        }

        $this->style = new Style("#".$this->getId());
    }

    /**
     * Registers a new Class for this element
     * @return HtmlElement $this
     */
    public function addClass($class_name){
        $this->classes["$class_name"] = true;
        $this->updateClasses();
        return $this;
    }

    /**
     * Remove a previously registered class
     */
    public function removeClass($class_name){
        if(isset($this->classes["$class_name"]))
        {
            unset($this->classes["$class_name"]);

        }
        $this->updateClasses();
        return $this;
    }

    /**
     * Reset the "class" attribute from the html tag
     */
    private function updateClasses(){
        $this->setAttribute('class', implode( ' ',array_keys($this->classes) ));
    }

    /**
     * Get the style
     * @return Styele
     */
    public function getStyle()
    {
        return $this->style;
    }

    /**
     * fetch or just return the HTML code for this element
     * @see Element::toCode
     */
    public function toHtml($echo = true){
        return $this->toCode($echo);
    }

    /**
     * Fetch or just return the associated CSS styles for this element and it's children
     * @param boolean $echo true if he code should be printed
     */
    public function toCSS($echo = TRUE){
        $css = $this->getStyle()->get(true);
        foreach(self::$childs as $child){
            $css.= $child['element']->toCSS(false);
        }

        if ($echo) {
            echo $css;
        }

        return $css;
    }

    /**
     * Overload the addChild so the added child would be HtmlElement
     * @see Element::addChild
     */
    public function addChild($child, $before = true, $position = -1) {
        if (! $child instanceof HtmlElement) {
            throw new DataTypeException($this);
        }
        parent::addChild($child, $before, $position);
        return $this;
    }

    /**
     * Provides a CSS-compatible #Selector
     */
    public function __toString(){
        return "#".$this->getId();
    }
}