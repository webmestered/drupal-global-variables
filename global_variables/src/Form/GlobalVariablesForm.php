<?php

namespace Drupal\global_variables\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class GlobalVariablesForm extends ConfigFormBase {

  protected function getEditableConfigNames() {
    return ['global_variables.settings'];
  }

  public function getFormId() {
    return 'global_variables_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config('global_variables.settings');
    $variables = $config->get('variables') ?? [];

    // Régi struktúra kompatibilitás
    foreach ($variables as $key => $item) {
      if (!is_array($item)) {
        $variables[$key] = [
          'label' => $key,
          'value' => $item,
          'weight' => 0,
        ];
      }
    }

    uasort($variables, function ($a, $b) {
      return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
    });

    $form['variables'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Megnevezés'),
        $this->t('Változó név'),
        $this->t('Érték'),
        $this->t('Sorrend'),
        $this->t('Token'),
      ],
      '#empty' => $this->t('No variables defined yet.'),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'variable-weight',
        ],
      ],
    ];

    foreach ($variables as $key => $item) {

      $form['variables'][$key]['#attributes']['class'][] = 'draggable';

      $form['variables'][$key]['label'] = [
        '#type' => 'textfield',
        '#default_value' => $item['label'],
      ];

      $form['variables'][$key]['key'] = [
        '#type' => 'textfield',
        '#default_value' => $key,
        '#size' => 15, // kb fele szélesség
      ];

      $form['variables'][$key]['value'] = [
        '#type' => 'textfield',
        '#default_value' => $item['value'],
      ];

      $form['variables'][$key]['weight'] = [
        '#type' => 'weight',
        '#default_value' => $item['weight'] ?? 0,
        '#attributes' => ['class' => ['variable-weight']],
      ];

      $form['variables'][$key]['token'] = [
        '#markup' => '<code>[global:' . $key . ']</code>',
      ];
    }

    // Új sor
    $form['variables']['new'] = [
      '#attributes' => ['class' => ['draggable']],
      'label' => [
        '#type' => 'textfield',
        '#placeholder' => 'Megnevezés',
      ],
      'key' => [
        '#type' => 'textfield',
        '#placeholder' => 'valtozo_nev',
        '#size' => 15,
      ],
      'value' => [
        '#type' => 'textfield',
        '#placeholder' => 'Érték',
      ],
      'weight' => [
        '#type' => 'weight',
        '#default_value' => 99,
        '#attributes' => ['class' => ['variable-weight']],
      ],
      'token' => [
        '#markup' => '<code>[global:valtozo_nev]</code>',
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $input = $form_state->getValue('variables');
    $clean = [];

    foreach ($input as $row) {
      if (!empty($row['key']) && !empty($row['value'])) {
        $clean[$row['key']] = [
          'label' => $row['label'] ?? $row['key'],
          'value' => $row['value'],
          'weight' => $row['weight'] ?? 0,
        ];
      }
    }

    $this->config('global_variables.settings')
      ->set('variables', $clean)
      ->save();

    parent::submitForm($form, $form_state);
  }

}