<?php

namespace Drupal\search_api_logger\Plugin\search_api\backend;

use Drupal\Core\Database\Query\SelectInterface;
use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api_db\Plugin\search_api\backend\Database as SearchApiDbBackend;
use Drupal\Core\Form\FormStateInterface;

/**
 * Database backend override for Search API Logger.
 *
 * As DB backend class doesn't use postQuery() method, unlike solr we
 * only can easily log query (not result).
 */
class SearchApiLoggerDbBackend extends SearchApiDbBackend {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $conf = parent::defaultConfiguration();
    $conf += [
      'log_query' => FALSE,
      'debug_query' => FALSE,
    ];
    return $conf;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['log_query'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log search requests'),
      '#description' => $this->t('Log all outgoing DB search requests.'),
      '#default_value' => $this->configuration['log_query'],
    ];
    $devel_module_present = \Drupal::moduleHandler()->moduleExists('devel');

    $form['debug_query'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug DB search requests (requires Devel module)'),
      '#description' => $this->t('Show DB search requests alongside search results pages.'),
      '#default_value' => $this->configuration['debug_query'] && $devel_module_present,
      '#disabled' => !$devel_module_present,
    ];

    return $form;
  }

  /**
   * Adds logging to database query.
   *
   * {@inheritdoc}
   */
  protected function preQuery(SelectInterface &$db_query, QueryInterface $query) {
    parent::preQuery($db_query, $query);

    if ($this->configuration['log_query']) {
      $this->getLogger()->notice($this->formatQuery($db_query));
    }

    if ($this->configuration['debug_query']) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        kint($this->formatQuery($query));
      }
    }
  }

  /**
   * Formatter for a query.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $db_query
   *   Database query.
   *
   * @return string
   *   Formatted text from query.
   */
  protected function formatQuery(SelectInterface $db_query) {
    $output = 'Search API Backend: db; Query: "' . $db_query->__toString();
    $arguments = $db_query->getArguments();
    if ($arguments) {
      $output .= '"; Arguments: ' . json_encode($arguments);
    }
    return $output;
  }

}
