<?php

namespace Drupal\joinup_migrate\Plugin\migrate\destination;

use Drupal\Core\Database\Connection;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\migrate\destination\DestinationBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination plugin for the Joinup mapping table.
 *
 * @MigrateDestination(
 *   id = "mapping"
 * )
 */
class Mapping extends DestinationBase implements ContainerFactoryPluginInterface {

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs a mapping_table destination plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, Connection $database) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    return [
      'nid' => ['type' => 'integer'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function fields(MigrationInterface $migration = NULL) {
    return [
      'nid' => $this->t('Source node ID'),
      'type' => $this->t('Source node-type'),
      'collection' => $this->t('Collection'),
      'policy' => $this->t('Policy domain 1'),
      'policy2' => $this->t('Policy domain 2'),
      'new_collection' => $this->t('Is new collection?'),
      'del' => $this->t('Delete?'),
      'abstract' => $this->t('Abstract'),
      'logo' => $this->t('Logo'),
      'banner' => $this->t('Banner'),
      'owner' => $this->t('Owner'),
      'admin_user' => $this->t('Administration User'),
      'elibrary' => $this->t('Elibrary Creation'),
      'pre_moderation' => $this->t('Pre Moderation'),
      'collection_state' => $this->t('Collection State'),
      'status' => $this->t('Status of the collection'),
      'row_index' => $this->t('Excel row index'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if (!$row->changed()) {
      return $old_destination_id_values;
    }

    $values = $row->getDestination();

    // Assure sane defaults for $values.
    foreach ($this->fields() as $key => $value) {
      if (!array_key_exists($key, $values)) {
        $values[$key] = NULL;
      }
    }

    $nid = $values['nid'];
    try {
      if (empty($old_destination_id_values)) {
        $this->database->insert('joinup_migrate_mapping')
          ->fields(array_keys($this->fields()))
          ->values($values)
          ->execute();
      }
      else {
        unset($values['nid']);
        $this->database->update('joinup_migrate_mapping')
          ->fields($values)
          ->condition('nid', $nid)
          ->execute();
      }
      return [$nid];
    }
    catch (MigrateException $exception) {
      throw new MigrateException($exception->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    parent::rollback($destination_identifier);
    $this->database->delete('joinup_migrate_mapping')
      ->condition('nid', $destination_identifier['nid'])
      ->execute();
  }

}