<?php
/**
 * @file
 * Contains \Drupal\d324_core\Plugin\Block\D324HeaderImageBlock.
 */

namespace Drupal\d324_core\Plugin\Block;
use Drupal\Component\Utility\Html;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Template\Attribute;
use Drupal\field\Entity\FieldConfig;
use Drupal\media\Entity\Media;

/**
 * Provides a block to display the header image for a page
 *
 * @Block(
 *   id = "d324_header_image_block",
 *   admin_label = @Translation("D324 Header Image block"),
 *   category = @Translation("D324 Blocks")
 * )
 */
class D324HeaderImageBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = [
      'label_display' => FALSE,
      'breakout_container' => TRUE,
      'include_page_title' => TRUE,
      'header_media_field' => '',
      'title_color' => [
        'color' => '',
        'opacity' => '',
      ],
      'dont_change_color_without_image' => TRUE,
      'overlay_color_1' => [
        'color' => '',
        'opacity' => '',
      ],
      'overlay_color_2' => [
        'color' => '',
        'opacity' => '',
      ],
      'hide_overlay_without_image' => TRUE,
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   *
   * Creates a generic configuration form for all block types. Individual
   * block plugins can add elements to this form by overriding
   * BlockBase::blockForm(). Most block plugins should not override this
   * method unless they need to alter the generic form elements.
   *
   * @see \Drupal\Core\Block\BlockBase::blockForm()
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['label']['#type'] = 'hidden';
    $form['label_display']['#default_value'] = 0;
    $form['label_display']['#type'] = 'hidden';

    $form['breakout_container'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('Breakout container'),
      '#description' => 'Make the header image fill the screen from edge to edge',
      '#default_value' => $config['breakout_container'],
    ];

    $form['include_page_title'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('Include page title'),
      '#description' => 'Overlay the page title directly over the header image',
      '#default_value' => $config['include_page_title'],
    ];

    $media_field_options = $this->getMediaFieldNameOptions();

    if (count($media_field_options) > 0) {
      $form['header_media_field'] = [
        '#type' => 'select',
        '#title' => $this->t('Header Image field'),
        '#description' => $this->t('Media field to be used as header image.'),
        '#options' => $media_field_options,
        '#empty_value' => '',
        '#default_value' => count($media_field_options) == 1 ? key($media_field_options) : $this->configuration['header_media_field'],
      ];
    }
    else {
      $form['message'] = [
        '#type' => 'container',
        '#markup' => $this->t('No media field type available. Please add at least one to an entity bundle type.'),
        '#attributes' => [
          'class' => ['messages messages--error'],
        ],
      ];
    }

    $moduleHandler = \Drupal::service('module_handler');
    if ($moduleHandler->moduleExists('color_field')){
      $form['#attached']['library'][] = 'color_field/color-field-widget-spectrum';
      $uid = Html::getUniqueId( 'title-color-field-field-color');
      $form['title_color'] = [
        '#type' => 'container',
        '#title' => 'Title Color: ',
        '#tree' => TRUE,
        '#attached' => [
          'drupalSettings' => [
            'color_field' => [
              'color_field_widget_spectrum' => [
                $uid => [
                  'show_input' => TRUE,
                  'show_palette' => TRUE,
                  'palette' => '["#333333","#FCFCFC","#005696","#555555","#11689B","#FEC114","#AEBED7","#175088","#F89406","#DC3545","#FEC114","#17A2B8","#28A745","#111111","#F0F0F0","#000000","#FFFFFF"]',
                  'show_buttons' => TRUE,
                  'allow_empty' => TRUE,
                  'show_palette_only' => FALSE,
                  'show_alpha' => TRUE,
                ],
              ],
            ],
          ],
        ],
        'color' => [
          '#title' => 'Title Color Value',
          '#type' => 'textfield',
          '#maxlength' => 7,
          '#size' => 7,
          '#attributes' => [
            'class' => [
              'js-color-field-widget-spectrum__color',
            ],
          ],
          '#default_value' => $config['title_color']['color'],
        ],
        'opacity' => [
          '#type' => 'number',
          '#min' => 0,
          '#max' => 1,
          '#step' => .1,
          '#attributes' => [
            'class' => [
              'js-color-field-widget-spectrum__opacity',
            ],
          ],
          '#default_value' => $config['title_color']['opacity'],
        ],
        '#show_alpha' => TRUE,
        '#attributes' => [
          'id' => $uid,
          'class' => 'js-color-field-widget-spectrum',
        ],
      ];
      $form['dont_change_color_without_image'] = [
        '#type' => 'checkbox',
        '#title' => $this
          ->t('Don\'t change title color without image'),
        '#description' => 'Only change the title color if there is a media image',
        '#default_value' => $config['dont_change_color_without_image'],
      ];
      $form['color_overlay'] = [
        '#type' => 'container',
        '#title' => 'Color Overlay',
      ];
      $form['color_overlay']['color_description'] = [
        '#markup' => '<p><small>Add 1 color for a solid color overlay.  Add 2 colors to form a gradient overlay.</small></p>',
      ];
      foreach([1,2] as $delta) {
        $uid = Html::getUniqueId( 'color-field-field-color_' . $delta);
        $form['color_overlay']['overlay_color_' . $delta] = [
          '#type' => 'container',
          '#title' => 'Color ' . $delta . ': ',
          '#tree' => TRUE,
          '#attached' => [
            'drupalSettings' => [
              'color_field' => [
                'color_field_widget_spectrum' => [
                  $uid => [
                    'show_input' => TRUE,
                    'show_palette' => TRUE,
                    'palette' => '["#333333","#FCFCFC","#1F2949","#555555","#11689B","#FEC114","#AEBED7","#175088","#F89406","#DC3545","#FEC114","#17A2B8","#28A745","#111111","#F0F0F0","#000000","#FFFFFF"]',
                    'show_buttons' => TRUE,
                    'allow_empty' => TRUE,
                    'show_palette_only' => FALSE,
                    'show_alpha' => TRUE,
                  ],
                ],
              ],
            ],
          ],
          'color' => [
            '#title' => 'Color Value ' . $delta,
            '#type' => 'textfield',
            '#maxlength' => 7,
            '#size' => 7,
            '#attributes' => [
              'class' => [
                'js-color-field-widget-spectrum__color',
              ],
            ],
            '#default_value' => $config['overlay_color_' . $delta]['color'],
          ],
          'opacity' => [
            '#type' => 'number',
            '#min' => 0,
            '#max' => 1,
            '#step' => .1,
            '#attributes' => [
              'class' => [
                'js-color-field-widget-spectrum__opacity',
              ],
            ],
            '#default_value' => $config['overlay_color_' . $delta]['opacity'],
          ],
          '#show_alpha' => TRUE,
          '#attributes' => [
            'id' => $uid,
            'class' => 'js-color-field-widget-spectrum',
          ],
        ];
      }
    }

    $form['hide_overlay_without_image'] = [
      '#type' => 'checkbox',
      '#title' => $this
        ->t('Hide overlay without image'),
      '#description' => 'Only show the overlay color if there is a media image',
      '#default_value' => $config['hide_overlay_without_image'],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    return [];
  }

  public function blockValidate($form, FormStateInterface $form_state) {
    parent::blockValidate($form, $form_state); // TODO: Change the autogenerated stub}
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['breakout_container'] = $form_state->getValue('breakout_container');
    $this->configuration['include_page_title'] = $form_state->getValue('include_page_title');
    $this->configuration['header_media_field'] = $form_state->getValue('header_media_field');
    $this->configuration['title_color'] = $form_state->getValue('title_color');
    $this->configuration['dont_change_color_without_image'] = $form_state->getValue('dont_change_color_without_image');
    $color_overlay = $form_state->getValue('color_overlay');
    $this->configuration['overlay_color_1'] = !empty($color_overlay['overlay_color_1']) ? $color_overlay['overlay_color_1'] : NULL;
    $this->configuration['overlay_color_2'] = !empty($color_overlay['overlay_color_2']) ? $color_overlay['overlay_color_2'] : NULL;
    $this->configuration['hide_overlay_without_image'] = $form_state->getValue('hide_overlay_without_image');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();
    $request = \Drupal::request();
    $route_match = \Drupal::routeMatch();

    $wrapper_attributes = new Attribute([
      'class' => 'header-image-wrapper',
    ]);

    $title_attributes = new Attribute( [
      'class' => 'page-title',
    ]);



    if($config['include_page_title']) {
      $route_object = $route_match->getRouteObject();
      $title = \Drupal::service('title_resolver')->getTitle($request, $route_object);
    }

    $media_field = $config['header_media_field'];
    if($media_field) {
      list($media_field_type, $media_field_name) = explode('.', $media_field);
      foreach (\Drupal::routeMatch()->getParameters() as $param) {
        if ($param instanceof \Drupal\Core\Entity\EntityInterface) {
          if($param->getEntityTypeId() == $media_field_type && $param->hasField($media_field_name)) {
            $page_entity = $param;
            break;
          }
        }
      }
      if (!isset($page_entity)) {
        $entity_type_manager = \Drupal::entityTypeManager();
        $types = $entity_type_manager->getDefinitions();
        foreach ($route_match->getParameters()->keys() as $param_key) {
          if (!isset($types[$param_key])) {
            continue;
          }
          if ($param = $route_match->getParameter($param_key)) {
            if (is_string($param) || is_numeric($param)) {
              try {
                $page_entity = $entity_type_manager->getStorage($param_key)->load($param);
              }
              catch (\Exception $e) {
              }
            }
            break;
          }
        }
      }
      if (isset($page_entity) && $page_entity->access('view')) {
        if(!empty($page_entity->get($media_field_name)->first())) {
          $media_id = $page_entity->get($media_field_name)->first()->getValue();
          if(!empty($media_id)) {
            $media_entity = Media::load($media_id['target_id']);
            $media = \Drupal::entityTypeManager()->getViewBuilder('media')->view($media_entity, 'se2e_4_1');
            $wrapper_attributes->addClass('has-header-background');
            if($config['breakout_container']) {
              $wrapper_attributes->addClass('breakout-container');
              $title_attributes->addClass('container');
            }
          }
        }
      }
    }

    if(!$config['dont_change_color_without_image'] || isset($media)) {
      if ($config['title_color']['color']) {
        list($r1, $g1, $b1) = sscanf($config['title_color']['color'], "#%02x%02x%02x");
        $a1 = $config['title_color']['opacity'];
        $title_style = "color: rgba($r1,$g1,$b1,$a1) !important; ";
        $title_attributes->setAttribute('id', $title_style);
      }
    }
    if(!$config['hide_overlay_without_image'] || isset($media)) {
      if($config['overlay_color_1']['color'] && $config['overlay_color_2']['color']) {
        list($r1, $g1, $b1) = sscanf($config['overlay_color_1']['color'], "#%02x%02x%02x");
        $a1 = $config['overlay_color_1']['opacity'];
        list($r2, $g2, $b2) = sscanf($config['overlay_color_2']['color'], "#%02x%02x%02x");
        $a2 = $config['overlay_color_2']['opacity'];
        $style = "background: linear-gradient(rgba($r1,$g1,$b1,$a1),rgba($r2,$g2,$b2,$a2)); ";
      } elseif($config['overlay_color_1']['color']) {
        list($r1, $g1, $b1) = sscanf($config['overlay_color_1']['color'], "#%02x%02x%02x");
        $a1 = $config['overlay_color_1']['opacity'];
        $style = "background: rgba($r1,$g1,$b1,$a1); ";
      }
      if(!empty($style)) {
        $overlay_attributes = new Attribute([
          'class' => ['header-overlay'],
          'style' => $style,
        ]);
        $wrapper_attributes->addClass('has-header-background');
        if($config['breakout_container']) {
          $wrapper_attributes->addClass('breakout-container');
          $title_attributes->addClass('container');
        }
      }
    }

    return array(
      '#theme' => 'd324_header_image_block',
      '#page_title' => isset($title) ? $title : '',
      '#media' => isset($media) ? $media : NULL,
      '#wrapper_attributes' => $wrapper_attributes,
      '#overlay_attributes' => isset($overlay_attributes) ? $overlay_attributes : NULL,
      '#title_attributes' => $title_attributes,
      '#attached' => [
        'library' => [
          'd324_core/header_image',
        ],
      ],
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getMediaFieldNameOptions() {
    $fields = [];
    $entity_fields = [];
    $entity_reference_fields = \Drupal::service('entity_field.manager')->getFieldMapByFieldType('entity_reference');
    foreach($entity_reference_fields as $entity_type_name => $entity_type) {
      foreach($entity_type as $field_name => $field) {
        if($field_name !== 'type') {
          if(!empty($field['bundles'])) {
            foreach($field['bundles'] as $bundle_name) {
              $entity_fields[$entity_type_name][$field_name] = $field_name;
            }
          }
        }
      }
    }
    foreach($entity_fields as $entity_type_name => $field_names) {
      foreach($field_names as $field_name) {
        $field_definition = \Drupal::entityTypeManager()->getStorage('field_storage_config')->load($entity_type_name . '.' . $field_name);
        if($field_definition && $field_definition->getSetting('target_type') == 'media') {
          $fields[$entity_type_name . '.' . $field_name] = $field_definition->getLabel();
        }
      }
    }
    return $fields;
  }

}
