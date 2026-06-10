<?php

namespace TransformStudios\Events;

use Statamic\Facades\Entry;
use Statamic\Tags\Collection\Entries as BaseEntries;

class Entries extends BaseEntries
{
    // we don't support these
    protected $ignoredParams = ['as', 'from', 'order_by', 'since', 'sort', 'until'];

    /*
        Same as the parent but removed the queries we don't support
    */
    protected function query()
    {
        $query = Entry::query()
            ->whereIn('collection', $this->collections->map->handle()->all());

        $this->querySite($query);
        $this->queryPublished($query);
        $this->queryTaxonomies($query);
        $this->queryConditions($query);
        $this->queryScopes($query);

        return $query;
    }
}
