<?php

namespace Drupal\collection\ContextProvider;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Plugin\Context\Context;
use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\Plugin\Context\ContextProviderInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\og\Og;
use Drupal\rdf_entity\Entity\Rdf;

/**
 * Sets the current collection as a context on collection routes.
 */
class CollectionRouteContext implements ContextProviderInterface {

  use StringTranslationTrait;

  /**
   * The route match object.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new CollectionRouteContext.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   */
  public function __construct(RouteMatchInterface $route_match) {
    $this->routeMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getRuntimeContexts(array $unqualified_context_ids) {
    $result = [];
    $context_definition = new ContextDefinition('entity:rdf_entity:collection', NULL, FALSE);
    $value = NULL;
    if (($route_object = $this->routeMatch->getRouteObject()) && ($route_contexts = $route_object->getOption('parameters')) && isset($route_contexts['rdf_entity'])) {
      /** @var \Drupal\rdf_entity\RdfInterface $collection */
      if ($collection = $this->routeMatch->getParameter('rdf_entity')) {
        if ($collection->bundle() == 'collection') {
          $value = $collection;
        }
      }
    }
    elseif ($route_parameters = $this->routeMatch->getParameters()) {
      /** @var ContentEntityInterface $route_parameter */
      foreach ($route_parameters as $route_parameter) {
        if ($route_parameter instanceof ContentEntityInterface) {
          $bundle = $route_parameter->bundle();
          $entity_type = $route_parameter->getEntityTypeId();

          // Check if the object is a og content entity.
          if (Og::isGroupContent($entity_type, $bundle) && ($groups = Og::getGroupIds($route_parameter, 'rdf_entity', 'collection'))) {
            // A content can belong to only one rdf_entity.
            $collection = Rdf::load(reset($groups['rdf_entity']));
            $value = $collection;
          }
        }
      }
    }

    $cacheability = new CacheableMetadata();
    $cacheability->setCacheContexts(['route']);

    $context = new Context($context_definition, $value);
    $context->addCacheableDependency($cacheability);

    $result['collection'] = $context;

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  public function getAvailableContexts() {
    $context = new Context(new ContextDefinition('entity:rdf_entity:collection', $this->t('Collection from URL')));
    return ['collection' => $context];
  }

}
