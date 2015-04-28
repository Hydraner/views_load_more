<?php
/**
 * @file
 * Container Drupal\views_load_more\Plugin\views\pager\LoadMore
 */

namespace Drupal\views_load_more\Plugin\views\pager;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\pager\Full;

/**
 * The plugin to handle full pager.
 *
 * @ingroup views_pager_plugins
 *
 * @ViewsPager(
 *   id = "load_more",
 *   title = @Translation("Load more pager"),
 *   short_title = @Translation("Load more"),
 *   help = @Translation("Paged output, each page loaded via AJAX."),
 *   theme = "views_load_more_pager",
 *   register_theme = FALSE
 * )
 */
class LoadMore extends Full {

  /**
   * Overrides \Drupal\views\Plugin\views\pager\Full::summaryTitle().
   */
  public function summaryTitle() {
    if (!empty($this->options['offset'])) {
      return $this->formatPlural($this->options['items_per_page'], 'Load more pager, @count item, skip @skip', 'Load more pager, @count items, skip @skip', array('@count' => $this->options['items_per_page'], '@skip' => $this->options['offset']));
    }
    return $this->formatPlural($this->options['items_per_page'], 'Load more pager, @count item', 'Load more pager, @count items', array('@count' => $this->options['items_per_page']));
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Full::defineOptions().
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['waypoint']['contains']['infinite'] = array('default' => FALSE);

    $options['more_button_text'] = array('default' => $this->t('Load more'));
    $options['end_text'] = array('default' => '');

    // @todo change name to content_selector
    $options['advanced']['contains']['content_class'] = array('default' => '');
    $options['advanced']['contains']['pager_selector'] = array('default' => '');

    $options['effects']['contains']['type'] = array('default' => 'none');
    $options['effects']['contains']['type'] = array('default' => '');

    return $options;
  }

  /**
   * Overrides \Drupal\views\Plugin\views\Full::buildOptionsForm().
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    // A couple settings are irrelevant for a Load More pager
    unset($form['tags']);
    unset($form['quantity']);

    // Keep items per page as the first form element on the page followed by
    // the option to change the 'load more' button text
    $form['items_per_page']['#weight'] = -2;

    // Option for users to specify the text used on the 'load more' button.
    $form['more_button_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('More link text'),
      '#description' => $this->t('The text that will be displayed on the link that is used to load more elements. For example "Show me more"'),
      '#default_value' => $this->options['more_button_text'] ? $this->options['more_button_text'] : $this->t('Load more'),
      '#weight' => -1,
    );

    // Option for users to specify the text shown when there are no more results
    $form['end_text'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('End text'),
      '#description' => $this->t('Optionally specify the text that is shown to the user in place of the pager link when the user has reached the end of the list, eg. "No more results".'),
      '#default_value' => $this->options['end_text'] ? $this->options['end_text'] : '',
      '#weight' => -1,
    );

    if (\Drupal::moduleHandler()->moduleExists('waypoints')) {
      $form['waypoint'] = array(
        '#type' => 'fieldset',
        '#collapsible' => FALSE,
        '#collapsed' => FALSE,
        '#tree' => TRUE,
        '#title' => $this->t('Waypoint Support'),
        '#input' => TRUE,
        '#description' => $this->t('Waypoints is a small jQuery plugin that makes it easy to execute a function whenever you scroll to an element.'),
      );

      $form['waypoint']['infinite'] = array(
        '#type' => 'checkbox',
        '#title' => $this->t('Infinite scrolling'),
        '#description' => $this->t('Load more content when the user reaches the bottom of the page.'),
        '#default_value' => $this->options['waypoint']['infinite'],
      );
    }

    // Advanced options, override default selectors.
    $form['advanced'] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#tree' => TRUE,
      '#title' =>  $this->t('Advanced Options'),
      '#description' => $this->t('Configure advanced options.'),
    );

    // Option to specify the content_class, which is the wrapping div for views
    // rows.  This allows the JS to both find new rows on next pages and know
    // where to put them in the page.
    // @todo change name to content_selector
    $form['advanced']['content_class'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Content selection selector'),
      '#description' => $this->t('jQuery selector for the rows wrapper, relative to the view container.  Use when overriding the views markup.  Note that Views Load More requires a wrapping element for the rows.  Unless specified, Views Load More will use <strong><code>&gt; .view-content</code></strong>.'),
      '#default_value' => $this->options['advanced']['content_class'],
    );

    // Option to specify the pager_selector, which is the pager relative to the
    // view container.
    $form['advanced']['pager_selector'] = array(
      '#type' => 'textfield',
      '#title' => $this->t('Pager selector'),
      '#description' => $this->t('jQuery selector for the pager, relative to the view container.  Use when overriding the pager markup so that Views Load More knows where to find and how to replace the pager.  Unless specified, Views Load More will use <strong><code>.pager-load-more</code></strong>.'),
      '#default_value' => $this->options['advanced']['pager_selector'],
    );

    // Affect the way that Views Load More adds new rows
    $form['effects'] = array(
      '#type' => 'details',
      '#open' => FALSE,
      '#tree' => TRUE,
      '#title' =>  $this->t('JQuery Effects'),
    );

    $form['effects']['type'] = array(
      '#type' => 'select',
      '#options' => array(
        'none' => $this->t('None'),
        'fade' => $this->t('Fade'),
        'slide' => $this->t('Slide'),
      ),
      '#default_vaue' => 'none',
      '#title' => $this->t('Effect Type'),
      '#default_value' => $this->options['effects']['type'],
    );

    $form['effects']['speed'] = array(
      '#type' => 'select',
      '#options' => array(
        'slow' => $this->t('Slow'),
        'fast' => $this->t('Fast'),
      ),
      '#states' => array(
        'visible' => array(
          array('#edit-pager-options-effects-type' => array('value' => 'fade')),
          array('#edit-pager-options-effects-type' => array('value' => 'slide')),
        ),
      ),
      '#title' => $this->t('Effect Speed'),
      '#default_value' => $this->options['effects']['speed'],
    );
  }

  /**
   * {@inheritdoc}
   */
  function render($parameters) {
    $output = array(
      '#theme' => $this->themeFunctions(),
      '#element' => $this->options['id'],
      '#parameters' => $parameters,
      '#more_button_text' => $this->options['more_button_text'],
      '#end_text' => $this->options['end_text'],
    );

    if (\Drupal::moduleHandler()->moduleExists('waypoints') && $this->options['waypoint']['infinite'] == 1) {
      $settings = array();
      $waypoint_opts = array(
        'offset' => '100%',
      );
      // Allow modules to alter the waypoint options.
      // @todo this makes more sense on the JS side I think.
      \Drupal::moduleHandler()->alter('views_load_more_waypoint_opts', $waypoint_opts, $this->view);

      $settings[$this->view->name . '-' . $this->view->current_display] = array(
        'view_name' => $this->view->name,
        'view_display_id' => $this->view->current_display,
        'waypoints' => 'infinite',
        'opts' => $waypoint_opts,
      );

      // Add the JS settings to the render array.
      $output['#attached'] = array(
        'library' => array(
          'waypoints/waypoints',
        ),
        'js' => array(
          array(
            'data' => $settings,
            'type' => 'setting',
          ),
        ),
      );
    }

    return $output;
  }
}
