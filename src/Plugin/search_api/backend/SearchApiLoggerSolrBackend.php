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
    ];
    return $conf;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['advanced']['log_query'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Log search requests'),
      '#description' => $this->t('Log all outgoing Solr search requests.'),
      '#default_value' => $this->configuration['log_query'],
    ];
    $form['advanced']['log_response'] = [
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
