<?php

namespace Drupal\Tests\field_ui\Functional;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the UI for entity displays.
 *
 * @group field_ui
 */
class EntityDisplayTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'entity_test', 'layout_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    EntityTest::create([
      'name' => 'The name for this entity',
      'field_test_text' => [[
        'value' => 'The field test text value',
      ]],
    ])->save();

    $this->drupalLogin($this->drupalCreateUser([
      'view test entity',
      'administer entity_test display',
    ]));
  }

  /**
   * Tests the use of regions for entity view displays.
   */
  public function testEntityView() {
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertSession()->elementExists('css', '.region-content-message.region-empty');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());
  }

  /**
   * Tests that layouts are unique per view mode.
   */
  public function testEntityViewModes() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // By default, the field is not visible.
    $this->drupalGet('entity_test/1/test');
    $assert_session->pageTextContains('test | The name for this entity');
    $assert_session->elementNotExists('css', '.field--name-field-test-text');
    $this->drupalGet('entity_test/1');
    $assert_session->pageTextContains('full | The name for this entity');
    $assert_session->elementNotExists('css', '.field--name-field-test-text');

    // Place the field in the content region.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $page->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->submitForm([], 'Save');

    // Switch to using the onecol layout.
    $page->selectFieldOption('layout', 'layout_test_1col');
    $this->submitForm([], 'Change layout');
    $this->submitForm([], 'Save');

    // Change the layout for the "test" view mode. See
    // core.entity_view_mode.entity_test.test.yml.
    $page->checkField('display_modes_custom[test]');
    $this->submitForm([], 'Save');
    $this->clickLink('configure them');
    $page->selectFieldOption('layout', 'layout_test_2col');
    $this->submitForm([], 'Change layout');
    $this->submitForm([], 'Save');

    // Move the field to a new region.
    $page->selectFieldOption('fields[field_test_text][region]', 'left');
    $this->submitForm([], 'Save');

    // Each view mode has a different layout.
    $this->drupalGet('entity_test/1/test');
    $assert_session->elementExists('css', '.layout-example-2col .region-left .field--name-field-test-text');
    $this->drupalGet('entity_test/1');
    $assert_session->elementExists('css', '.layout-example-1col .region-top .field--name-field-test-text');
  }

  /**
   * Tests that changes to the regions still leave the fields visible.
   */
  public function testRegionChanges() {
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertEquals(['Content', 'Disabled'], $this->getRegionTitles());
    // Move the field to the content region.
    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->submitForm([], 'Save');

    // Set the test module to remove the content region.
    \Drupal::state()->set('layout_test.alter_regions', TRUE);
    \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

    // The field is still shown on the page, but now in the Disabled region.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertEquals(['Foo', 'Disabled'], $this->getRegionTitles());
    $this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden');
  }

  /**
   * Gets the region titles on the page.
   *
   * @return string[]
   *   An array of region titles.
   */
  protected function getRegionTitles() {
    $region_titles = [];
    $region_title_elements = $this->getSession()->getPage()->findAll('css', '.region-title td');
    /** @var \Behat\Mink\Element\NodeElement[] $region_title_elements */
    foreach ($region_title_elements as $region_title_element) {
      $region_titles[] = $region_title_element->getText();
    }
    return $region_titles;
  }

}
