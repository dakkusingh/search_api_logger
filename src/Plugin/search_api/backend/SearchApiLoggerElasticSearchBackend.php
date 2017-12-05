<?php

namespace Drupal\search_api_logger\Plugin\search_api\backend;

use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\elasticsearch_connector\Plugin\search_api\backend\SearchApiElasticsearchBackend;
use Drupal\Core\Form\FormStateInterface;

/**
 * Elastic Search backend for Search API Logger.
 */
class SearchApiLoggerElasticSearchBackend extends SearchApiElasticsearchBackend {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $conf = parent::defaultConfiguration();
    $conf += [
      'log_query' => FALSE,
      'log_response' => FALSE,
      'debug_query' => FALSE,
      'debug_results' => FALSE,
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
      '#description' => $this->t('Log all outgoing Elastic Search search requests.'),
      '#default_value' => $this->configuration['log_query'],
    ];
    $devel_module_present = \Drupal::moduleHandler()->moduleExists('devel');

    $form['debug_query'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Elastic Search queries (requires Devel module)'),
      '#description' => $this->t('Show Elastic Search queries alongside search results pages.'),
      '#default_value' => $this->configuration['debug_query'] && $devel_module_present,
      '#disabled' => !$devel_module_present,
    ];
    $form['log_response'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log search results'),
      '#description' => $this->t('Log all search result responses received from Elastic Search.'),
      '#default_value' => $this->configuration['log_response'],
    ];
    $form['debug_results'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Elastic Search results (requires Devel module)'),
      '#description' => $this->t('Show Elastic Search results alongside search results pages.'),
      '#default_value' => $this->configuration['debug_results'] && $devel_module_present,
      '#disabled' => !$devel_module_present,
    ];

    return $form;
  }

  /**
   * Adds logging to Elastic Search query.
   *
   * {@inheritdoc}
   */
  protected function preQuery(QueryInterface $query) {
    parent::preQuery($query);

    if ($this->configuration['log_query']) {
      $this->getLogger()->notice($this->formatQuery($query));
    }

    if ($this->configuration['debug_query']) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        kint([
          'IndexId' => $query->getIndex()->id(),
          'Query' => $query->getOriginalKeys(),
          'Fields' => $query->getFulltextFields(),
          'Sorts' => $query->getSorts(),
        ]);
      }
    }
  }

  /**
   * Adds logging of Elastic Search result.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   Results set.
   *
   * {@inheritdoc}
   */
  protected function postQuery(ResultSetInterface $results, QueryInterface $query, $response) {
    parent::postQuery($results, $query, $response);

    if ($this->configuration['log_response']) {
      $this->getLogger()->notice($this->formatResponse($results));
    }

    if ($this->configuration['debug_results']) {
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        kint($results->getResultItems());
      }
    }
  }

  /**
   * Formatter for a query.
   *
   * @param \Drupal\search_api\Query\QueryInterface $query
   *   Elastic Search query.
   *
   * @return string
   *   Formatted text from query.
   */
  protected function formatQuery(QueryInterface $query) {
    return 'Search API Backend: elasticsearch; Query: ' . $query->getOriginalKeys() . '; Fields: ' . $query->__toString(array_keys($query->getFulltextFields()));
  }

  /**
   * Formatter for a Elastic Search response.
   *
   * @param \Drupal\search_api\Query\ResultSetInterface $results
   *   Results set.
   *
   * @return string
   *   Response in the text form.
   */
  protected function formatResponse(ResultSetInterface $results) {
    $output = 'Result count: ' . $results->getResultCount();
    if ($results->getResultCount()) {
      $output .= '; Result items: ' . implode(',', array_keys($results->getResultItems()));
    }
    return $output;
  }

}
