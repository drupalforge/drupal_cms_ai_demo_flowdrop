<?php

declare(strict_types=1);

namespace Drupal\mcp_client;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides a listing of MCP Servers.
 */
final class McpServerListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, mixed>
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['id'] = $this->t('Machine name');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, mixed>
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\mcp_client\McpServerInterface $entity */
    $row['label'] = $entity->label();
    $row['id'] = $entity->id();
    $row['status'] = $entity->status() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

}
