<?php

namespace Drupal\bhcc_guide\Plugin\Block;

use Drupal\bhcc_helper\CurrentPage;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class GuideContentsBlock
 *
 * @package Drupal\bhcc_guide\Plugin\Block
 *
 * @Block(
 *   id = "bhcc_guide_contents",
 *   admin_label = "Guide contents"
 * )
 */
class GuideContentsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * @var bool|\Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\bhcc_guide\Plugin\Block\GuideContentsBlock|\Drupal\Core\Plugin\ContainerFactoryPluginInterface
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('bhcc_helper.current_page')
    );
  }

  /**
   * GuideContentsBlock constructor.
   *
   * @param array $configuration
   * @param $plugin_id
   * @param $plugin_definition
   * @param \Drupal\bhcc_helper\CurrentPage $currentPage
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, CurrentPage $currentPage) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->node = $currentPage->getNode();
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $build = [];

    $links = [];
    foreach ($this->node->listGuidePages() as $guide_node) {
      if ($guide_node->getGuideSectionTitle()) {
        $links[] = Link::fromTextAndUrl($guide_node->getGuideSectionTitle(), Url::fromRoute('entity.node.canonical', ['node' => $guide_node->id()]));
      }
    }

    $build[] = [
      '#theme' => 'guide_contents',
      '#links' => $links,
      '#cache' => [
        'tags' => $this->prepareCacheTags($this->node, ...$this->node->listGuidePages()),
      ],
    ];

    return $build;
  }

  /**
   * Prepare cache tags for the given items.
   */
  protected function prepareCacheTags(CacheableDependencyInterface ...$cacheable_items): array {

    $list_of_tag_collections = array_map(function ($cacheable_item) {
      return $cacheable_item->getCacheTags();
    }, $cacheable_items);

    $merged_tags = array_reduce($list_of_tag_collections, [Cache::class, 'mergeTags'], $initial = []);
    return $merged_tags;
  }

}
