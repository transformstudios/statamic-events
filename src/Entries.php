<?php

namespace TransformStudios\Events;

use Statamic\Tags\Collection\Entries as BaseEntries;

class Entries extends BaseEntries
{
    // public function get()
    // {
    //     try {
    //         $query = $this->query();
    //     } catch (NoResultsExpected $exception) {
    //         return new EntryCollection;
    //     }

    //     return $this->results($query);
    // }

    protected function query()
    {
        $query = Entry::query()
            ->whereIn('collection', $this->collections->map->handle()->all());

        $this->querySelect($query);
        $this->querySite($query);
        $this->queryPublished($query);
        $this->queryTaxonomies($query);
        $this->queryConditions($query);
        $this->queryScopes($query);

        return $query;
    }
}
