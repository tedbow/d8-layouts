<?php

namespace Drupal\Tests\Core\Entity\Display;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\WidgetPluginManager;
use Drupal\Core\Form\FormState;
use Drupal\Core\Layout\LayoutDefault;
use Drupal\Core\Layout\LayoutDefinition;
use Drupal\Core\Layout\LayoutPluginManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity\EntityFormDisplay
 * @group Entity
 */
class EntityFormDisplayTest extends UnitTestCase {

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

    $widget_manager = $this->prophesize(WidgetPluginManager::class);

    $this->entityFieldManager = $this->prophesize(EntityFieldManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('entity_field.manager', $this->entityFieldManager->reveal());
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    $container->set('plugin.manager.field.widget', $widget_manager->reveal());
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
    $non_configurable_field_definition->isDisplayConfigurable('form')->willReturn(FALSE);

    $definitions = [];
    $definitions['non_configurable_field'] = $non_configurable_field_definition->reveal();
    $this->entityFieldManager->getFieldDefinitions('the_entity_type_id', 'the_entity_type_bundle')->willReturn($definitions);

    $this->entityFieldManager->getExtraFields('the_entity_type_id', 'the_entity_type_bundle')->willReturn([]);

    $build = [
      'test1' => [
        '#markup' => 'Test1',
      ],
      'test2' => [
        '#markup' => 'Test2',
        '#group' => 'existing_group',
      ],
      'layout' => [
        '#markup' => 'Field created through the UI happens to be named "Layout"',
      ],
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
    ];

    $display = new EntityFormDisplay(
      [
        'targetEntityType' => 'the_entity_type_id',
        'bundle' => 'the_entity_type_bundle',
        'layout_id' => 'two_column',
        'layout_settings' => [],
        'content' => [
          'test1' => [
            'region' => 'right',
          ],
          'test2' => [
            'region' => 'left',
          ],
          'layout' => [
            'region' => 'right',
          ],
          'non_configurable_field' => [
            'region' => 'left',
          ],
        ],
      ],
      'entity_form_display'
    );

    $expected = [
      'test1' => [
        '#markup' => 'Test1',
        '#group' => 'right',
      ],
      'test2' => [
        '#markup' => 'Test2',
        '#group' => 'existing_group',
      ],
      'layout' => [
        '#markup' => 'Field created through the UI happens to be named "Layout"',
        '#group' => 'right',
      ],
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
      '_layout' => [
        'left' => [
          '#process' => ['\Drupal\Core\Render\Element\RenderElement::processGroup'],
          '#pre_render' => ['\Drupal\Core\Render\Element\RenderElement::preRenderGroup'],
        ],
        'right' => [
          '#process' => ['\Drupal\Core\Render\Element\RenderElement::processGroup'],
          '#pre_render' => ['\Drupal\Core\Render\Element\RenderElement::preRenderGroup'],
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

    $build = $display->applyLayout($build, new FormState(), []);
    $this->assertEquals($expected, $build);
    $this->assertSame($expected, $build);
  }

  /**
   * @covers ::applyLayout
   * @covers ::getFields
   */
  public function testApplyLayoutEmpty() {
    $definitions = [];
    $non_configurable_field_definition = $this->prophesize(FieldDefinitionInterface::class);
    $non_configurable_field_definition->isDisplayConfigurable('form')->willReturn(FALSE);
    $definitions['non_configurable_field'] = $non_configurable_field_definition->reveal();
    $this->entityFieldManager->getFieldDefinitions('the_entity_type_id', 'the_entity_type_bundle')->willReturn($definitions);
    $this->entityFieldManager->getExtraFields('the_entity_type_id', 'the_entity_type_bundle')->willReturn([]);

    $build = [
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
    ];

    $display = new EntityFormDisplay(
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
        ],
      ],
      'entity_form_display'
    );

    $expected = [
      'non_configurable_field' => [
        '#markup' => 'Non-configurable',
      ],
    ];
    $build = $display->applyLayout($build, new FormState(), []);
    $this->assertSame($expected, $build);
  }

}
