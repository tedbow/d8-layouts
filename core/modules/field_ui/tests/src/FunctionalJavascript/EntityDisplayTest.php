<?php

namespace Drupal\Tests\field_ui\FunctionalJavascript;

use Drupal\entity_test\Entity\EntityTest;
use Drupal\FunctionalJavascriptTests\JavascriptTestBase;

/**
 * Tests the UI for entity displays.
 *
 * @group field_ui
 */
class EntityDisplayTest extends JavascriptTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['field_ui', 'entity_test', 'layout_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $entity = EntityTest::create([
      'name' => 'The name for this entity',
      'field_test_text' => [[
        'value' => 'The field test text value',
      ]],
    ]);
    $entity->save();
    $this->drupalLogin($this->drupalCreateUser([
      'access administration pages',
      'view test entity',
      'administer entity_test content',
      'administer entity_test fields',
      'administer entity_test display',
      'administer entity_test form display',
      'view the administration theme',
    ]));
  }

  /**
   * Tests the use of regions for entity form displays.
   */
  public function testEntityForm() {
    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertSession()->fieldExists('field_test_text[0][value]');

    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $this->assertEquals(['Content', 'Disabled'], $this->getRegionTitles());
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());

    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'hidden');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->drupalGet('entity_test/manage/1/edit');
    $this->assertSession()->fieldNotExists('field_test_text[0][value]');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Restore the field to the default region.
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $page->selectFieldOption('fields[field_test_text][region]', 'content');
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // Switch the layout to two columns.
    $this->click('#edit-layouts');
    $page->selectFieldOption('layout', 'layout_test_2col');
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // The field is moved to the default region for the new layout.
    $assert_session->pageTextContains('Your settings have been saved.');
    $this->assertEquals(['Left region', 'Right region', 'Disabled'], $this->getRegionTitles());

    $this->drupalGet('entity_test/manage/1/edit');
    // No fields are visible, and the regions don't display when empty.
    $region_element = $page->find('css', '.region-left');
    $this->assertNotNull($region_element);
    $assert_session->fieldExists('field_test_text[0][value]', $region_element);

    // After a refresh the new regions are still there.
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $this->assertEquals(['Left region', 'Right region', 'Disabled'], $this->getRegionTitles());

    // Drag the field to the right region.
    $field_test_text_row = $page->find('css', '#field-test-text');
    $right_region_row = $page->find('css', '.region-right-message');
    $field_test_text_row->find('css', '.handle')->dragTo($right_region_row);
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // The new layout is used.
    $this->drupalGet('entity_test/manage/1/edit');
    $assert_session->elementExists('css', '.region-right .field--name-field-test-text');
    $region_element = $page->find('css', '.region-right');
    $this->assertNotNull($region_element);
    $assert_session->fieldExists('field_test_text[0][value]', $region_element);

    // Move the field to the right region without tabledrag.
    $this->drupalGet('entity_test/structure/entity_test/form-display');
    $page->pressButton('Show row weights');
    $page->selectFieldOption('fields[field_test_text][region]', 'right');
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // The updated region is used.
    $this->drupalGet('entity_test/manage/1/edit');
    $region_element = $page->find('css', '.region-right');
    $this->assertNotNull($region_element);
    $assert_session->fieldExists('field_test_text[0][value]', $region_element);

    // The layout is still in use without Field UI.
    $this->container->get('module_installer')->uninstall(['field_ui']);
    $this->drupalGet('entity_test/manage/1/edit');
    $region_element = $page->find('css', '.region-right');
    $this->assertNotNull($region_element);
    $assert_session->fieldExists('field_test_text[0][value]', $region_element);
  }

  /**
   * Tests the use of regions for entity view displays.
   */
  public function testEntityView() {
    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementNotExists('css', '.field--name-field-test-text');

    $this->drupalGet('entity_test/structure/entity_test/display');
    // The one-column layout is in use.
    $this->assertEquals(['Content', 'Disabled'], $this->getRegionTitles());
    $this->assertSession()->elementExists('css', '.region-content-message.region-empty');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());

    $this->getSession()->getPage()->selectFieldOption('fields[field_test_text][region]', 'content');
    $this->assertSession()->assertWaitOnAjaxRequest();
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
    $this->assertTrue($this->assertSession()->optionExists('fields[field_test_text][region]', 'content')->isSelected());

    $this->drupalGet('entity_test/1');
    $this->assertSession()->elementExists('css', '.field--name-field-test-text');

    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    // Restore the field to the hidden region.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $page->selectFieldOption('fields[field_test_text][region]', 'hidden');
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    // Switch the layout to two columns.
    $this->click('#edit-layouts');
    $page->selectFieldOption('layout', 'layout_test_2col');
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    $assert_session->pageTextContains('Your settings have been saved.');
    $this->assertEquals(['Left region', 'Right region', 'Disabled'], $this->getRegionTitles());

    $this->drupalGet('entity_test/1');
    // No fields are visible, and the regions don't display when empty.
    $assert_session->elementNotExists('css', '.layout-example-2col');
    $assert_session->elementNotExists('css', '.region-left');
    $assert_session->elementNotExists('css', '.field--name-field-test-text');

    // After a refresh the new regions are still there.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->assertEquals(['Left region', 'Right region', 'Disabled'], $this->getRegionTitles());

    // Drag the field to the left region.
    $this->assertTrue($assert_session->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());
    $field_test_text_row = $page->find('css', '#field-test-text');
    $left_region_row = $page->find('css', '.region-left-message');
    $field_test_text_row->find('css', '.handle')->dragTo($left_region_row);
    $assert_session->assertWaitOnAjaxRequest();
    $this->assertFalse($assert_session->optionExists('fields[field_test_text][region]', 'hidden')->isSelected());
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // The new layout is used.
    $this->drupalGet('entity_test/1');
    $assert_session->elementExists('css', '.layout-example-2col');
    $assert_session->elementExists('css', '.region-left .field--name-field-test-text');

    // Move the field to the right region without tabledrag.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $page->pressButton('Show row weights');
    $page->selectFieldOption('fields[field_test_text][region]', 'right');
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');
    $assert_session->pageTextContains('Your settings have been saved.');

    // The updated region is used.
    $this->drupalGet('entity_test/1');
    $assert_session->elementExists('css', '.region-right .field--name-field-test-text');

    // The layout is still in use without Field UI.
    $this->container->get('module_installer')->uninstall(['field_ui']);
    $this->drupalGet('entity_test/1');
    $assert_session->elementExists('css', '.layout-example-2col');
    $assert_session->elementExists('css', '.region-right .field--name-field-test-text');
  }

  /**
   * Tests extra fields.
   */
  public function testExtraFields() {
    entity_test_create_bundle('bundle_with_extra_fields');
    $this->drupalGet('entity_test/structure/bundle_with_extra_fields/display');

    $extra_field_row = $this->getSession()->getPage()->find('css', '#display-extra-field');
    $disabled_region_row = $this->getSession()->getPage()->find('css', '.region-hidden-title');

    $extra_field_row->find('css', '.handle')->dragTo($disabled_region_row);
    $this->assertSession()->assertWaitOnAjaxRequest();

    $this->submitForm([], 'Save');
    $this->assertSession()->pageTextContains('Your settings have been saved.');
  }

  /**
   * Tests layout plugins with forms.
   */
  public function testLayoutForms() {
    $assert_session = $this->assertSession();
    $page = $this->getSession()->getPage();

    $this->drupalGet('entity_test/structure/entity_test/display');
    // Switch to a layout with settings.
    $this->click('#edit-layouts');

    // Test switching between layouts with and without forms.
    $page->selectFieldOption('layout', 'layout_test_plugin');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('settings_wrapper[layout_settings][setting_1]');

    $page->selectFieldOption('layout', 'layout_test_2col');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldNotExists('settings_wrapper[layout_settings][setting_1]');

    $page->selectFieldOption('layout', 'layout_test_plugin');
    $assert_session->assertWaitOnAjaxRequest();
    $assert_session->fieldExists('settings_wrapper[layout_settings][setting_1]');

    // Move the test field to the content region.
    $page->pressButton('Show row weights');
    $page->selectFieldOption('fields[field_test_text][region]', 'content');
    $assert_session->assertWaitOnAjaxRequest();
    $this->submitForm([], 'Save');

    $this->drupalGet('entity_test/1');
    $assert_session->pageTextContains('Blah: Default');

    // Update the layout settings.
    $this->drupalGet('entity_test/structure/entity_test/display');
    $this->click('#edit-layouts');
    $page->fillField('settings_wrapper[layout_settings][setting_1]', 'Test text');
    $this->submitForm([], 'Save');

    $this->drupalGet('entity_test/1');
    $assert_session->pageTextContains('Blah: Test text');
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
