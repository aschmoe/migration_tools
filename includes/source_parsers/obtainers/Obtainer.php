<?php

/**
 * @file
 * Class Obtainer
 */

class Obtainer {
  // Properties declaration.
  /**
   * @var object
   *   QueryPath element that was the last thing found.
   */
  protected $justFound;

  /**
   * @var string
   *   Text that has not been evaluated yet.
   */
  protected $possibleText;

  /**
   * @var object
   *   QueryPath object passed in at instantiation.
   */
  protected $queryPath;

  /**
   * @var string
   *   The name of the findMethod that is currently running.
   */
  protected $currentFindMethod = '';

  /**
   * @var array
   *   Array of find methods to call, in order.  Passed in at instantiation.
   */
  protected $targetStack = array();

  /**
   * @var string
   *   String of text that was trimmed off during trim in cleanup.  It was
   *   removed from the dom so this is the only place to prevent it from getting
   *   lost.
   */
  protected $textDiscarded = '';

  /**
   * @var string
   *   String of text that has been cleaned and evaluated and is the Title.
   */
  protected $textToReturn = '';


  /**
   * Constructor for the Obtainer.
   *
   * @param object $query_path
   *   The query path object to use as the source of possible content.
   * @param array $target_stack
   *   Array of find methods to run through.
   */
  public function __construct($query_path, $target_stack = '') {
    if (!empty($target_stack) && !empty($query_path) && is_array($target_stack)) {
      $this->setTargetStack($target_stack);
      $this->queryPath = $query_path;
    }
  }

  // ******************** Getters and Setters ***************************.


  /**
   * Basic setter for the query path element that was just found by the finder.
   */
  protected function setJustFound($qp_element) {
    // Since this is a reference to an element in the query path, must clear it
    // before giving it a new element.
    unset($this->justFound);
    $this->justFound = $qp_element;
  }

  /**
   * Basic getter for the query path element that was just found by the finder.
   */
  protected function getJustFound() {
    return $this->justFound;
  }


  /**
   * Basic setter for text found that might be returned if validated.
   */
  protected function setPossibleText($text) {
    $this->possibleText = $text;
  }

  /**
   * Basic getter for text found that might be returned if validated.
   */
  protected function getPossibleText() {
    return $this->possibleText;
  }


  /**
   * Basic setter for the target finder method currently running/just ran.
   */
  protected function setCurrentFindMethod($target) {
    $this->currentFindMethod = $target;
  }

  /**
   * Basic getter for the target finder method currently running/just ran.
   */
  protected function getCurrentFindMethod() {
    return $this->currentFindMethod;
  }


  /**
   * Basic setter for text that is discarded after truncation.
   */
  protected function setTextDiscarded($text) {
    $text = trim($text);
    if (!empty($text)) {
      $this->textDiscarded = $text;
      // Alert that there is something discared.
      drush_doj_migration_debug_output("WARNING: Discarded Text vvv Use  {instance}->getTextDiscarded()");
    }
  }

  /**
   * Basic public getter for text that is discarded after truncation.
   */
  public function getTextDiscarded() {
    return $this->textDiscarded;
  }


  /**
   * Basic setter for the target finder method that gave useable results.
   */
  protected function setTargetStack($stack = array()) {
    if (is_array($stack)) {
      $this->targetStack = $stack;
    }
  }

  /**
   * Basic getter for the target finder method that gave useable results.
   */
  protected function getTargetStack() {
    return $this->targetStack;
  }


  /**
   * Basic setter for text that is validated and ready to return.
   */
  protected function setTextToReturn($text) {
    $this->textToReturn = $text;
  }

  /**
   * Basic getter for text that is validated and ready to return.
   */
  protected function getTextToReturn() {
    return $this->textToReturn;
  }

  /**
   * Basic getter public alias of getTextToReturn().
   */
  public function getText() {
    return $this->getTextToReturn();
  }


  // ************* Methods ***************************.

  /**
   * Removes $justFound from the dom if it is not empty.
   */
  protected function removeFoundDomElement() {
    $jf = $this->getJustFound();
    if (!empty($jf)) {
      $this->getJustFound()->remove();
    }
    // Break the reference to the QueryPath.
    $this->clearJustFound();
  }


  /**
   * Extracts, validates a string from html and puts remainder into the source.
   *
   * @param string $string
   *   The string of text to validated and remove.
   * @param object $qp_element
   *   The queryPath element to alter and put back.
   */
  protected function extractAndPutBack($string, $qp_element) {
    // Clean string.
    $processed_text = $this->cleanPossibleText($string);
    // Evaluate string.
    $this->setPossibleText($processed_text);
    $valid = $this->validatePossibleText();
    if ($valid) {
      // The string checks out, remove the original string from the element.
      $full_source = $qp_element->html();
      $new_source = str_replace($string, '', $full_source);
      $qp_element->html($new_source);
      // Since we have already surgically removed just the string, don't remove
      // the entire element.
      $this->removeMeNot();
    }
  }


  /**
   * Clean PossibleText prior to validation.
   */
  protected function cleanThisPossibleText() {
    $text = $this->getPossibleText();
    $text = $this->cleanPossibleText($text);
    $this->setPossibleText($text);
  }


  /**
   * Clears the ability to remove $justFound from the querypath object.
   */
  protected function clearJustFound() {
    // Just unset the $justFound so it can not be removed from the querypath.
    unset($this->justFound);
  }


  /**
   * Alias for clearJustFound.  Prevents removal of justFound from the QP.
   */
  protected function removeMeNot() {
    // An alias for clearJustFound.
    $this->clearJustFound();
  }


  /**
   * Takes a string, returns anything before a <br> tag and its many variants.
   *
   * @param string $text
   *   The text to break at the first <br> variant.
   *
   * @return string
   *   The string appearing before the <br> or the full string if no <br>.
   */
  public static function trimAtBr($text = '') {
    // Replace variations of br tag.
    // @codingStandardsIgnoreStart
    $search = array(
      '<br>',
      '<br />',
      '<br/>',
    );
    $text = str_ireplace($search, '<br>', $text);
    $texts = explode('<br>', $text);
    // @codingStandardsIgnoreEnd

    return $texts[0];
  }

  /**
   * Is essentially a truncation hook for each class to extend if needed.
   */
  protected function truncateThisPossibleText() {
    // This does nothing in the base class.
    // Add your own copy of this to extended classes to create a truncation.
  }


  /**
   * Strips html, truncates to word boundary, and preserves what was left.
   *
   * @param string $text
   *   Html or plain text to be truncated.
   * @param int $length
   *   The number of characters to truncate to.
   * @param int $min_word_length
   *   Minimum number of characters to consider something as a word.
   *
   * @return array
   *   - truncated: Plain text that has been truncated.
   *   - remaining: Plain text that was left.
   */
  public static function truncateThisWithoutHTML($text = '', $length = 255, $min_word_length = 2) {
    $text = strip_tags($text);
    $trunc_text = truncate_utf8($text, $length, TRUE, FALSE, $min_word_length);
    // Check to see if any truncation is made.
    if (strcmp($text, $trunc_text) != 0) {
      // There was truncation, so process it differently.
      // Grab the remaning text by removing $trunc_test.
      $remaining_text = str_replace($trunc_text, '', $text);
    }
    $return = array(
      'truncated' => $trunc_text,
      'remaining' => (!empty($remaining_text)) ? $remaining_text : '',
    );

    return $return;
  }


  /**
   * Processes the entire method stack cleaning and evaluating as it goes.
   *
   * @param object $query_path
   *   The query path object for with the content for searching.
   *
   * @param array $target_stack
   *   The array of find methods to call.
   *
   * @param string $obtainer_name
   *   The name of the obtainer calling this method, used only for debug output.
   */
  protected function processMethodStack($query_path, $target_stack, $obtainer_name) {
    // Look for the target stack.
    if (!empty($target_stack) && !empty($query_path) && is_array($target_stack)) {
      // Loop through the stack.
      foreach ($this->getTargetStack() as $target) {
        // Check to see if this target has a method.
        if (method_exists($this, $target)) {
          // Set CurrentFindMethod.
          $this->setCurrentFindMethod($target);
          // Run the method to get $possibleText.
          $this->setPossibleText($this->$target());
          // Clean up the $possibleText.
          $this->cleanThisPossibleText();
          // Possible truncation available.
          $this->truncateThisPossibleText();
          // Evaluate the $possibleText.
          if ($this->validatePossibleText()) {
            // Set $textToReturn to $possibleText.
            $this->setTextToReturn($this->getPossibleText());
            // Remove the element from the dom.
            $this->removeFoundDomElement();

            // We have what we need, get out.
            break;
          }
          else {
            // Current Find Method found nothing useable.
            $this->setCurrentFindMethod('');
          }

        }
        else {
          // Output a message that the method does not exist.
          drush_doj_migration_debug_output("The target method '{$target}' in {$obtainer_name} does not exist and was skipped.");
        }
      }

    }
    $cfm = $this->getCurrentFindMethod();
    if (!empty($cfm)) {
      drush_doj_migration_debug_output("{$obtainer_name}-Matched: {$this->getCurrentFindMethod()}");
    }
    else {
      drush_doj_migration_debug_output("{$obtainer_name}-Matched: NO MATCHES FOUND");
    }

  }


  /**
   * A default findMethod for use when you want nothing found.
   *
   * @return string
   *   An empty text.
   */
  private function findNothing() {
    return '';
  }
}
