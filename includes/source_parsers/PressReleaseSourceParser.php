<?php

/**
 * @file
 * Class DistrictPressReleaseSourceParser
 */

class PressReleaseSourceParser extends SourceParser {


  /**
   * Defines and returns an array of parsing methods to call in order.
   *
   * @return array
   *   An array of parssing methods to call in order.
   */
  public function getParseOrder() {
    // Specify the parsing methods that should run in order.
    return array(
      // Getting the title relies on html that could be wiped during clean up
      // so let's get it before we clean things up.
      'setTitle',
      'setDate',
      // The title is set, so let's clean our html up.
      'cleanHtml',
      // With clean html we can get the body out.
      'setBody',
    );
  }

  /**
   * Setter.
   */
  public function setBody($body = '') {
    // If the first paragraph in the content div says archive, lets remove it.
    $elem = HtmlCleanUp::matchText($this->queryPath, ".contentSub > div > p", "Archives");
    if ($elem) {
      $elem->remove();
    }

    $elem = HtmlCleanUp::matchText($this->queryPath, "table", "FOR IMMEDIATE RELEASE");
    if ($elem) {
      $elem->remove();
    }

    // Build selectors to remove.
    $selectors = array(
      "#PRhead1",
      "#navWrap",
      "#Layer3",
      "#Layer4",
      "img",
      "h1",
      ".breadcrumb",
      ".newsLeft",
      "#widget",
      "#footer",
      "a[title='Printer Friendly']",
      "a[href='#top']",
      "a[href='http://www.justice.gov/usao/wvn']",
      "a[href='https://www.justice.gov/usao/wvn']",
    );
    HtmlCleanUp::removeElements($this->queryPath, $selectors);

    parent::setBody($body);
  }


  /**
   * Sets $this->title.
   *
   * This is duplicated so that we can use ObtainTitlePressRelease obtainer.
   */
  protected function setTitle($title = '') {
    try {
      if (empty($title)) {
        $method_stack = $this->getObtainerMethods('title');
        if (empty($method_stack)) {
          $method_stack = array(
            'findH1Any' => array(),
            'findIdContentstartDivH2Sec' => array(),
            'findH2First' => array(),
            'findClassContentSubDivPCenterStrong' => array(),
            'findClassContentSubDivDivPStrong' => array(),
            'findIdHeadline' => array(),
            'findPStrongEm' => array(),
            'findIdContentstartDivH2' => array(),
            'findDivClassContentSubDivDivCenter' => array(),
          );
        }
        $this->setObtainerMethods(array('title' => $method_stack));
        $title = $this->runObtainer('ObtainTitlePressRelease', 'title');
      }

      $this->title = $title;
      // Output to show progress to aid debugging.
      $this->sourceParserMessage('Title found --> @title', array('@title' => $this->title), WATCHDOG_DEBUG, 1);
    }
    catch (Exception $e) {
      $this->title = '';
      $this->sourceParserMessage("Error setting title.", array(), WATCHDOG_ERROR, 1);
    }
  }

  /**
   * Setter.
   */
  protected function setDate($date = '') {
    if (empty($date)) {
      $method_stack = $this->getObtainerMethods('date');
      if (empty($method_stack)) {
        // Set obtainer date stack to use if one has not been set by arguments.
        $method_stack = array(
          'findTableRow1Col2' => array(),
          'findTableRow1Col1' => array(),
          'findTable2Row2Col2' => array(),
          'findPAlignCenter' => array(),
          'findIdContentstartFirst' => array(),
          'findClassNewsRight' => array(),
          'findClassBottomLeftContent' => array(),
          'findProbableDate' => array(),
        );
      }
      $this->setObtainerMethods(array('date' => $method_stack));
    }

    return parent::setDate($date);
  }
}
