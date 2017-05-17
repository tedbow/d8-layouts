<?php

namespace Drupal\KernelTests\Core\Entity;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\KernelTests\KernelTestBase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity\EntityViewDisplay
 * @group Entity
 */
class EntityViewDisplayTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['entity_test', 'layout_test'];

  /**
   * @covers ::preSave
   * @covers ::calculateDependencies
   */
  public function testPreSave() {
    // Create an entity display with one hidden and one visible field.
    $entity_display = EntityViewDisplay::create([
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'status' => TRUE,
      'content' => [
        'foo' => ['type' => 'visible'],
        'bar' => ['type' => 'hidden'],
        'name' => ['type' => 'hidden', 'region' => 'content'],
      ],
    ]);

    $expected = [
      'langcode' => 'en',
      'status' => TRUE,
      'dependencies' => [],
      'id' => 'entity_test.entity_test.default',
      'targetEntityType' => 'entity_test',
      'bundle' => 'entity_test',
      'mode' => 'default',
      'content' => [
        'foo' => [
          'type' => 'visible',
        ],
        'bar' => [
          'type' => 'hidden',
        ],
      ],
      'hidden' => [],
      'layout_id' => 'layout_default',
      'layout_settings' => [],
    ];
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Save the display.
    // the 'content' property and the visible field has the default region set.
    $entity_display->save();

    // The dependencies have been updated.
    $expected['dependencies']['module'] = [
      'entity_test',
    ];
    // A third party setting is added by the entity_test module.
    $expected['third_party_settings']['entity_test'] = ['foo' => 'bar'];
    // The visible field is assigned the default region.
    $expected['content']['foo']['region'] = 'content';
    // The hidden field is removed from the list of visible fields, and marked
    // as hidden.
    unset($expected['content']['bar']);
    $expected['hidden'] = ['bar' => TRUE];

    $this->assertEntityValues($expected, $entity_display->toArray());

    // Assign a new layout that has default settings and complex dependencies,
    // but do not save yet.
    $entity_display->setLayoutFromId('test_layout_main_and_footer');

    $expected['layout_id'] = 'test_layout_main_and_footer';
    // The field was moved to the default region.
    $expected['content']['foo'] = [
      'type' => 'visible',
      'region' => 'main',
      'weight' => -4,
      'settings' => [],
      'third_party_settings' => [],
    ];
    $this->assertEntityValues($expected, $entity_display->toArray());

    $entity_display->save();
    // After saving, the dependencies have been updated.
    $expected['dependencies']['module'] = [
      'dependency_from_annotation',
      'dependency_from_calculateDependencies',
      'entity_test',
      'layout_test',
    ];
    // The default settings were added.
    $expected['layout_settings'] = [
      'setting_1' => 'Default',
    ];
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Assign a layout with provided settings.
    $entity_display->setLayoutFromId('test_layout_main_and_footer', ['setting_1' => 'foobar']);
    $entity_display->save();

    // The setting overrides the default value.
    $expected['layout_settings']['setting_1'] = 'foobar';
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Move a field to the non-default region.
    $component = $entity_display->getComponent('foo');
    $component['region'] = 'footer';
    $entity_display->setComponent('foo', $component);
    $entity_display->save();

    // The field region is saved.
    $expected['content']['foo']['region'] = 'footer';
    $this->assertEntityValues($expected, $entity_display->toArray());

    // Assign a different layout that shares the same non-default region.
    $entity_display->setLayoutFromId('test_layout_content_and_footer');
    $entity_display->save();

    // The dependencies have been updated.
    $expected['dependencies']['module'] = [
      'entity_test',
      'layout_test',
    ];
    // The layout has been updated.
    $expected['layout_id'] = 'test_layout_content_and_footer';
    $expected['layout_settings'] = [];
    // The field remains in its current region instead of moving to the default.
    $this->assertEntityValues($expected, $entity_display->toArray());
  }

  /**
   * Asserts than an entity has the correct values.
   *
   * @param mixed $expected
   * @param array $values
   * @param string $message
   */
  public static function assertEntityValues($expected, array $values, $message = '') {

    static::assertArrayHasKey('uuid', $values);
    unset($values['uuid']);

    static::assertEquals($expected, $values, $message);
  }

}
