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
 *   id = "mymovies"
 * )
 */
class MyMovies extends SqlBase {
    /**
     * {@inheritdoc}
     */
    public function query() {
        $query = $this->select('movies', 'm')
            ->fields('m', ['id', 'title', 'plot', 'actors', 'genre']);
        return $query;
    }
    /**
     * {@inheritdoc}
     */
    public function fields() {
        $fields = [
            'id' => $this->t('Movie ID'),
            'title' => $this->t('Movie name'),
            'plot' => $this->t('Movie plot'),
            'actors' => $this->t('Movie Actors'),
            'genre' => $this->t('Movie Genre'),
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
                'alias' => 'm',
            ],
        ];
    }
}