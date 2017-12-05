<?php

namespace Drupal\search_api_logger\Plugin\search_api\backend;

use Drupal\search_api\Query\QueryInterface;
use Drupal\search_api\Query\ResultSetInterface;
use Drupal\search_api_solr\Plugin\search_api\backend\SearchApiSolrBackend;
use Drupal\Core\Form\FormStateInterface;
use Solarium\Core\Query\QueryInterface as SolariumQueryInterface;

/**
 * Apache Solr backend for Search API Logger.
 */
class SearchApiLoggerSolrBackend extends SearchApiSolrBackend {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $conf = parent::defaultConfiguration();
    $conf += [
      'log_query' => FALSE,
      'log_response' => FALSE,
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
      '#description' => $this->t('Log all outgoing Solr search requests.'),
      '#default_value' => $this->configuration['log_query'],
    ];
    $devel_module_present = \Drupal::moduleHandler()->moduleExists('devel');

    $form['debug_query'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Debug Solr queries (requires Devel module)'),
      '#description' => $this->t('Add the debugQuery Solr parameter to each query and show alongside search results pages.'),
      '#default_value' => $this->configuration['debug_query'] && $devel_module_present,
      '#disabled' => !$devel_module_present,
    ];
    $form['log_response'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log search results'),
      '#description' => $this->t('Log all search result responses received from Solr.'),
      '#default_value' => $this->configuration['log_response'],
    ];

    return $form;
  }

  /**
   * Adds logging to solr query.
   *
   * {@inheritdoc}
   */
  protected function preQuery(SolariumQueryInterface $solarium_query, QueryInterface $query) {
    parent::preQuery($solarium_query, $query);

    if ($this->configuration['log_query']) {
      $this->getLogger()->notice($this->formatQuery($solarium_query));
    }

    if ($this->configuration['debug_query']) {
      $solarium_query->addParam('debugQuery', true);
    }

  }

  /**
   * Adds logging of solr result.
   *
   * {@inheritdoc}
   */
  protected function postQuery(ResultSetInterface $results, QueryInterface $query, $response) {
    parent::postQuery($results, $query, $response);

    if ($this->configuration['log_response']) {
      $this->getLogger()->notice($this->formatResponse($results));
    }

    if ($this->configuration['debug_query']) {
      $response_data = $results->getAllExtraData();
      if (\Drupal::moduleHandler()->moduleExists('devel')) {
        kint($response_data['search_api_solr_response']);
      }
    }
  }

  /**
   * Formatter for a query.
   *
   * @param \Solarium\Core\Query\QueryInterface $solarium_query
   *   Solarium query.
   *
   * @return string
   *   Formatted text from query.
   */
  protected function formatQuery(SolariumQueryInterface $solarium_query) {
    return 'Search API Backend: solr; Connector: ' . $this->getSolrConnector()->pluginId . '; Query: ' . $solarium_query->getOption('query') . '; Fields: ' . $solarium_query->getOption('fields');
  }

  /**
   * Formatter for a solr response.
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
