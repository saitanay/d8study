<?php
/**
 * @file
 * Contains \Drupal\demo\Plugin\migrate\source\Genres
 */
namespace Drupal\mar30\Plugin\migrate\source;
use Drupal\migrate\Plugin\migrate\source\SqlBase;
/**
 * Source plugin for the genres.
 *
 * @MigrateSource(
 *   id = "actors"
 * )
 */
class Actors extends SqlBase {
    /**
     * {@inheritdoc}
     */
    public function query() {
        $query = $this->select('actors', 'a')
            ->fields('a', ['id', 'name']);
        return $query;
    }
    /**
     * {@inheritdoc}
     */
    public function fields() {
        $fields = [
            'id' => $this->t('Actor ID'),
            'name' => $this->t('Actor name'),
        ];
        return $fields;
    }
    /**
     * {@inheritdoc}
     */
    public function getIds() {
        return [
            'id' => [
                'type' => 'integer',
                'alias' => 'id',
            ],
        ];
    }
}