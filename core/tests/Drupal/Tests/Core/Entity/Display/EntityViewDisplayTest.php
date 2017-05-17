<?php

namespace Drupal\Tests\Core\Entity\Display;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FormatterPluginManager;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity\EntityViewDisplay
 * @group Entity
 */
class EntityViewDisplayTest extends UnitTestCase {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\Prophecy\Prophecy\ProphecyInterface
   */
  protected $entityFieldManager;

  /**
   * A layout definition.
   *
   * @var \Drupal\Core\Layout\LayoutDefinition
   */
  protected $pluginDefinition;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->pluginDefinition = new LayoutDefinition([
      'library' => 'system/drupal.layout.twocol',
      'theme_hook' => 'layout__twocol',
      'regions' => [
        'left' => [
          'label' => 'Left',
        ],
        'right' => [
          'label' => 'Right',
        ],
      ],
    ]);
    $layout_plugin = new LayoutDefault([], 'two_column', $this->pluginDefinition);

    $layout_plugin_manager = $this->prophesize(LayoutPluginManagerInterface::class);
    $layout_plugin_manager->getDefinition('unknown', FALSE)->willReturn(NULL);
    $layout_plugin_manager->getDefinition('two_column', FALSE)->willReturn($this->pluginDefinition);
    $layout_plugin_manager->createInstance('two_column', [])->willReturn($layout_plugin);

    $renderer = $this->prophesize(RendererInterface::class);

    $entity_type = $this->prophesize(EntityTypeInterface::class);
    $entity_type->entityClassImplements(FieldableEntityInterface::class)->willReturn(TRUE);

    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $entity_type_manager->getDefinition('the_entity_type_id')->willReturn($entity_type->reveal());

    $formatter_manager = $this->prophesize(FormatterPluginManager::class);

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager->reveal());
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('plugin.manager.field.formatter', $formatter_manager->reveal());
    $container->set('renderer', $renderer->reveal());
    $container->set('plugin.manager.core.layout', $layout_plugin_manager->reveal());
    \Drupal::setContainer($container);
  }

  /**
   * @covers ::applyLayout
   * @covers ::getFields
   */
  public function testApplyLayout() {
    $non_configurable_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $non_configurable_field_definition->isDisplayConfigurable('view')->willReturn(FALSE);

    $definitions = [];
    $definitions['non_configurable_field'] = $non_configurable_field_definition->reveal();
    $definitions['non_configurable_field_with_extra_field'] = $non_configurable_field_definition->reveal();
    $this->entityFieldManager->getFieldDefinitions('the_entity_type_id', 'the_entity_type_bundle')->willReturn($definitions);

    $extra_fields = [];
    $extra_fields['display']['non_configurable_field_with_extra_field'] = [
      'label' => 'This non-configurable field is also defined in hook_entity_extra_field_info()',
    ];
    $this->entityFieldManager->getExtraFields('the_entity_type_id', 'the_entity_type_bundle')->willReturn($extra_fields);

    $build = [
      'test1' => [
        '#markup' => 'Test1',
      ],
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
      'non_configurable_field_with_extra_field' => [
        '#markup' => 'Non-configurable with extra field',
      ],
    ];

    $display = new EntityViewDisplay(
      [
        'targetEntityType' => 'the_entity_type_id',
        'bundle' => 'the_entity_type_bundle',
        'layout_id' => 'two_column',
        'layout_settings' => [],
        'content' => [
          'test1' => [
            'region' => 'right',
          ],
          'non_configurable_field' => [
            'region' => 'left',
          ],
          'non_configurable_field_with_extra_field' => [
            'region' => 'left',
          ],
        ],
      ],
      'entity_view_display'
    );

    $expected = [
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
      '_layout' => [
        'left' => [
          'non_configurable_field_with_extra_field' => [
            '#markup' => 'Non-configurable with extra field',
          ],
        ],
        'right' => [
          'test1' => [
            '#markup' => 'Test1',
          ],
        ],
        '#settings' => [],
        '#layout' => $this->pluginDefinition,
        '#theme' => 'layout__twocol',
        '#attached' => [
          'library' => [
            'system/drupal.layout.twocol',
          ],
        ],
      ],
    ];

    $method_ref = new \ReflectionMethod($display, 'applyLayout');
    $method_ref->setAccessible(TRUE);
    $method_ref->invokeArgs($display, [&$build]);
    $this->assertEquals($expected, $build);
    $this->assertSame($expected, $build);

    // Use getFieldFromBuild() to manipulate the array.
    $field_element = &$display->getFieldFromBuild('test1', $build);
    $field_element['#title'] = 'My title';
    $expected['_layout']['right']['test1']['#title'] = 'My title';
    $this->assertEquals($expected, $build);
  }

}
