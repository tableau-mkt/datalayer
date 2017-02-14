<?php

/**
 * @file
 * Contains \Drupal\datalayer\Form\DatalayerSettingsForm.
 */

namespace Drupal\datalayer\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Core\Url;

class DatalayerSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('datalayer.settings');
    $config->set('add_page_meta', $form_state->getValue('add_page_meta'))
      ->set('output_terms', $form_state->getValue('output_terms'))
      ->set('output_fields', $form_state->getValue('output_fields'))
      ->set('lib_helper', $form_state->getValue('lib_helper'))
      ->set('entity_meta', $form_state->getValue('global_entity_meta'))
      ->set('vocabs', $form_state->getValue('vocabs'))
      ->set('expose_user_details', $form_state->getValue('expose_user_details'))
      ->set('expose_user_details_roles', $form_state->getValue('expose_user_details_roles'))
      ->set('expose_user_details_fields', $form_state->getValue('expose_user_details_fields'))
      ->set('entity_title', $form_state->getValue('entity_title'))
      ->set('entity_type', $form_state->getValue('entity_type'))
      ->set('entity_bundle', $form_state->getValue('entity_bundle'))
      ->set('entity_identifier', $form_state->getValue('entity_identifier'))
      ->set('drupal_language', $form_state->getValue('drupal_language'))
      ->set('drupal_country', $form_state->getValue('drupal_country'))
      ->set('site_name', $form_state->getValue('site_name'))
      ->set('label_replacements', $this->labelReplacementsToArray($form_state->getValue('label_replacements')))
      ->save();

    if (\Drupal::moduleHandler()->moduleExists('group')) {
      $config->set('group', $form_state->getValue('group'))
        ->set('group_label', $form_state->getValue('group_label'))
        ->save();
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function labelReplacementsToArray($replacements) {
    $labels = explode("\r\n", $replacements);
    foreach( $labels as $label ){
      $tmp = explode( '|', $label );
      $storage[ $tmp[0] ] = $tmp[1];
    }
    return $storage;
  }

  /**
   * {@inheritdoc}
   */
  protected function labelReplacementsFromArray($replacements) {
    $display = '';
    if (!is_null($replacements)) {
      foreach ($replacements as $label => $replacement) {
        $display .= $label . "|" . $replacement . "\r\n";
      }
      return $display;
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['datalayer.settings'];
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    // Setup vocabs.
    $vocabs = Vocabulary::loadMultiple();
    $v_options = [];
    foreach ($vocabs as $v) {
      $v_options[$v->id()] = $v->label();
    }
    $datalayer_settings = $this->config('datalayer.settings');

    // Get available meta data.
    $meta_data = _datalayer_collect_meta_properties();

    $form['global'] = [
      '#type' => 'fieldset',
      '#title' => t('Global'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
    ];
    $form['global']['add_page_meta'] = [
      '#type' => 'checkbox',
      '#title' => t('Add entity meta data to pages'),
      '#default_value' => $datalayer_settings->get('add_page_meta'),
    ];
    $form['global']['output_terms'] = [
      '#type' => 'checkbox',
      '#states' => [
        'enabled' => [
          ':input[name="add_page_meta"]' => [
            'checked' => TRUE
            ]
          ]
        ],
      '#title' => t('Include taxonomy terms'),
      '#default_value' => $datalayer_settings->get('output_terms'),
    ];
    $form['global']['output_fields'] = [
      '#type' => 'checkbox',
      '#description' => t('Exposes a checkbox on field settings forms to expose data.'),
      '#title' => t('Include enabled field values'),
      '#default_value' => $datalayer_settings->get('output_fields'),
    ];
    $form['global']['lib_helper'] = [
      '#type' => 'checkbox',
      '#title' => t('Include "data layer helper" library'),
      '#default_value' => $datalayer_settings->get('lib_helper'),
      '#description' => t('Provides the ability to process messages passed to the dataLayer. See: <a href=":helper">data-layer-helper</a> on GitHub.', [
        ':helper' => 'https://github.com/google/data-layer-helper'
      ]),
    ];
    if (\Drupal::moduleHandler()->moduleExists('group')) {
      $form['global']['group'] = [
        '#type' => 'checkbox',
        '#title' => t('Group module support'),
        '#default_value' => $datalayer_settings->get('group'),
        '#description' => t('Output the group entities on pages beloging to a group.'),
      ];
    }

    $form['entity_meta'] = [
      '#type' => 'fieldset',
      '#title' => t('Entity meta data'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('The meta data details to ouput for client-side consumption. Marking none will output everything available.'),
    ];
    $form['entity_meta']['global_entity_meta'] = [
      '#type' => 'checkboxes',
      '#states' => [
        'enabled' => [
          ':input[name="add_page_meta"]' => [
            'checked' => TRUE
            ]
          ]
        ],
      '#title' => '',
      '#default_value' => $datalayer_settings->get('entity_meta'),
      '#options' => array_combine($meta_data, $meta_data),
    ];

    $form['vocabs'] = [
      '#type' => 'fieldset',
      '#title' => t('Taxonomy'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('The vocabularies which should be output within page meta data. Marking none will output everything available.'),
    ];
    $form['vocabs']['vocabs'] = [
      '#type' => 'checkboxes',
      '#states' => [
        'enabled' => [
          ':input[name="output_terms"]' => [
            'checked' => TRUE
            ]
          ]
        ],
      '#title' => '',
      '#default_value' => $datalayer_settings->get('vocabs'),
      '#options' => $v_options,
    ];

    $form['user'] = [
      '#type' => 'fieldset',
      '#title' => t('User Details'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('Details about the current user can be output to the dataLayer.'),
    ];

    $form['user']['expose_user_details'] = [
      '#type' => 'textarea',
      '#title' => t('Expose user details'),
      '#default_value' => $datalayer_settings->get('expose_user_details'),
      '#description' => t('Pages that should expose active user details to the dataLayer. Leaving empty will expose nothing.'),
    ];

    $user_roles =  user_roles(TRUE);
    $role_options = [];
    foreach ($user_roles as $id => $role) {
      $role_options[$id] = $role->label();
    }
    $form['user']['expose_user_details_roles'] = [
      '#type' => 'checkboxes',
      '#options' => $role_options,
      '#multiple' => TRUE,
      '#title' => t('Expose user roles'),
      '#default_value' => $datalayer_settings->get('expose_user_details_roles'),
      '#description' => t('Roles that should expose active user details to the dataLayer. Leaving empty will expose to all roles.'),
    ];

    $form['user']['expose_user_details_fields'] = [
      '#type' => 'checkbox',
      '#title' => t('Include enabled user field values'),
      '#default_value' => $datalayer_settings->get('expose_user_details_fields'),
    ];

    $form['output'] = [
      '#type' => 'fieldset',
      '#title' => t('Data layer output Labels'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
      '#description' => t('Define labels used in the datalayer output, labels for field values are configurable from the field edit form.'),
    ];

    // Entity title
    $entityTitle = $datalayer_settings->get('entity_title');
    $form['output']['entity_title'] = [
      '#type' => 'textfield',
      '#title' => t('Entity title'),
      '#default_value' => isset($entityTitle) ? $entityTitle : 'entityLabel',
      '#description' => t('Label for the title of the entity'),
    ];

    // Entity type.
    $entityType = $datalayer_settings->get('entity_type');
    $form['output']['entity_type'] = [
      '#type' => 'textfield',
      '#title' => t('Entity type'),
      '#default_value' => isset($entityType) ? $entityType : 'entityType',
      '#description' => t('Label for the type of the entity'),
    ];

    // Entity bundle.
    $entityBundle = $datalayer_settings->get('entity_bundle');
    $form['output']['entity_bundle'] = [
      '#type' => 'textfield',
      '#title' => t('Entity bundle'),
      '#default_value' => isset($entityBundle) ? $entityBundle : 'entityBundle',
      '#description' => t('Label for the bundle of the entity'),
    ];

    // Entity indetifier.
    $entityIdentifier = $datalayer_settings->get('entity_identifier');
    $form['output']['entity_identifier'] = [
      '#type' => 'textfield',
      '#title' => t('Entity identifier'),
      '#default_value' => isset($entityIdentifier) ? $entityIdentifier : 'entityIdentifier',
      '#description' => t('Label for the identifier of the entity'),
    ];

    // drupalLanguage.
    $drupalLanguage = $datalayer_settings->get('drupal_language');
    $form['output']['drupal_language'] = [
      '#type' => 'textfield',
      '#title' => t('Drupal language'),
      '#default_value' => isset($drupalLanguage) ? $drupalLanguage : 'drupalLanguage',
      '#description' => t('Label for the language of the Drupal site'),
    ];

    // drupalCountry.
    $drupalCountry = $datalayer_settings->get('drupal_country');
    $form['output']['drupal_country'] = [
      '#type' => 'textfield',
      '#title' => t('Drupal country'),
      '#default_value' => isset($drupalCountry) ? $drupalCountry : 'drupalCountry',
      '#description' => t('Label for the country of the Drupal site'),
    ];

    if (\Drupal::moduleHandler()->moduleExists('group')) {
      // Group label.
      $groupLabel = $datalayer_settings->get('group_label');
      $form['output']['group_label'] = [
        '#type' => 'textfield',
        '#title' => t('Group label'),
        '#default_value' => isset($groupLabel) ? $groupLabel : 'groupLabel',
        '#description' => t('Label for the group label'),
      ];
    }

    // Site name.
    $drupalSitename = $datalayer_settings->get('site_name');
    $form['output']['site_name'] = [
      '#type' => 'textfield',
      '#title' => t('Drupal site name'),
      '#default_value' => isset($drupalSitename) ? $drupalSitename : 'drupalSitename',
      '#description' => t('Label for the sitename value'),
    ];

    // find an replace.
    $labelReplacements = $datalayer_settings->get('label_replacements');
    $form['output']['label_replacements'] = [
      '#type' => 'textarea',
      '#title' => t('Exposed field sub label replacements'),
      '#default_value' => isset($labelReplacements) ? $this->labelReplacementsFromArray($labelReplacements) : '',
      '#description' => t('For exposed fields with a sub array of field daya you can enter a replacement value for labels using the format: returned_value|replacement'),
    ];

    return parent::buildForm($form, $form_state);
  }

}
